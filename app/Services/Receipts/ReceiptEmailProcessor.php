<?php

namespace App\Services\Receipts;

use App\Models\GmailReceiptImport;
use App\Services\AI\DocumentScannerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Receipt Email Processor
 *
 * Downloads receipt attachments, stores them, and scans with AI.
 */
class ReceiptEmailProcessor
{
    public function __construct(
        private readonly ReceiptEmailFetcher $fetcher,
        private readonly DocumentScannerService $scanner
    ) {
    }

    /**
     * Process receipt messages and scan attachments.
     */
    public function processMessages(array $messages): array
    {
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($messages as $message) {
            $messageId = $message['message_id'] ?? null;
            if (! $messageId) {
                continue;
            }

            foreach ($message['attachments'] ?? [] as $attachment) {
                $attachmentId = $attachment['attachment_id'] ?? null;
                $filename = $attachment['filename'] ?? null;

                if (! $attachmentId) {
                    $results['skipped']++;
                    continue;
                }

                $existing = GmailReceiptImport::where('message_id', $messageId)
                    ->where('attachment_id', $attachmentId)
                    ->first();

                if ($existing) {
                    $results['skipped']++;
                    continue;
                }

                $import = GmailReceiptImport::create([
                    'message_id' => $messageId,
                    'thread_id' => $message['thread_id'] ?? null,
                    'attachment_id' => $attachmentId,
                    'attachment_filename' => $filename,
                    'received_at' => $this->parseReceivedAt($message['internal_date_ms'] ?? null),
                    'status' => 'pending',
                ]);

                try {
                    $fileContents = $this->fetcher->downloadAttachment($messageId, $attachmentId);
                    if (! $fileContents) {
                        throw new \RuntimeException('Attachment download failed');
                    }

                    $storedPath = $this->storeAttachment($fileContents, $filename);
                    $tempPath = $this->writeTempFile($fileContents, $filename);

                    $scanResult = $this->scanner->scanDocument(
                        $tempPath,
                        DocumentScannerService::TYPE_INVOICE
                    );

                    @unlink($tempPath);

                    $import->update([
                        'status' => $scanResult['success'] ? 'processed' : 'failed',
                        'scan_log_id' => $scanResult['scan_log_id'] ?? null,
                        'error_message' => $scanResult['success'] ? null : ($scanResult['error'] ?? 'Scan failed'),
                    ]);

                    $results[$scanResult['success'] ? 'processed' : 'failed']++;

                    Log::info('ReceiptEmailProcessor: Processed attachment', [
                        'message_id' => $messageId,
                        'attachment_id' => $attachmentId,
                        'stored_path' => $storedPath,
                        'scan_log_id' => $scanResult['scan_log_id'] ?? null,
                    ]);
                } catch (\Throwable $e) {
                    $import->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                    $results['failed']++;

                    Log::error('ReceiptEmailProcessor: Failed to process attachment', [
                        'message_id' => $messageId,
                        'attachment_id' => $attachmentId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * Store attachment in configured storage.
     */
    protected function storeAttachment(string $contents, ?string $filename): ?string
    {
        $disk = config('receipts.storage_disk') ?: config('ai.scan_file_storage_disk', 'local');
        $basePath = config('receipts.storage_path') ?: config('ai.scan_file_storage_path', 'document-scans');
        $extension = $filename ? pathinfo($filename, PATHINFO_EXTENSION) : 'bin';
        $safeFilename = Str::uuid()->toString() . '.' . strtolower($extension ?: 'bin');

        $path = trim($basePath, '/').'/gmail/'.date('Y/m').'/'.$safeFilename;

        Storage::disk($disk)->put($path, $contents);

        return $path;
    }

    /**
     * Write a temp file for scanning.
     */
    protected function writeTempFile(string $contents, ?string $filename): string
    {
        $extension = $filename ? pathinfo($filename, PATHINFO_EXTENSION) : 'bin';
        $tempPath = tempnam(sys_get_temp_dir(), 'gmail-receipt-');

        if ($extension) {
            $newPath = $tempPath . '.' . $extension;
            rename($tempPath, $newPath);
            $tempPath = $newPath;
        }

        file_put_contents($tempPath, $contents);

        return $tempPath;
    }

    /**
     * Parse Gmail internal date milliseconds into Carbon.
     */
    protected function parseReceivedAt(?string $internalDateMs): ?Carbon
    {
        if (! $internalDateMs) {
            return null;
        }

        $timestamp = (int) floor(((int) $internalDateMs) / 1000);
        return Carbon::createFromTimestamp($timestamp);
    }
}
