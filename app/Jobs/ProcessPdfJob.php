<?php

namespace App\Jobs;

use App\Models\PdfDocument;
use App\Services\PdfProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process PDF Job
 *
 * Background job to process uploaded PDF documents:
 * - Extract pages
 * - Generate thumbnails
 * - Extract text
 * - Update processing status
 */
class ProcessPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying
     *
     * @var int
     */
    public $backoff = 30;

    /**
     * Number of seconds the job can run before timing out
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * The PDF document to process
     *
     * @var PdfDocument
     */
    public PdfDocument $document;

    /**
     * Create a new job instance
     *
     * @param PdfDocument $document
     */
    public function __construct(PdfDocument $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job
     *
     * @param PdfProcessingService $processingService
     * @return void
     */
    public function handle(PdfProcessingService $processingService): void
    {
        Log::info("ProcessPdfJob started", [
            'document_id' => $this->document->id,
            'file_name' => $this->document->file_name,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Mark as processing
            $this->document->markAsProcessing();

            // Process the PDF
            $processingService->processPdf($this->document);

            // Mark as completed
            $this->document->markAsCompleted();

            Log::info("ProcessPdfJob completed successfully", [
                'document_id' => $this->document->id,
                'pages_extracted' => $this->document->page_count,
            ]);

        } catch (\Exception $e) {
            // Mark as failed
            $this->document->markAsFailed($e->getMessage());

            Log::error("ProcessPdfJob failed", [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // If we haven't exhausted all attempts, throw to trigger retry
            if ($this->attempts() < $this->tries) {
                throw $e;
            }

            // Log final failure
            Log::critical("ProcessPdfJob exhausted all retries", [
                'document_id' => $this->document->id,
                'file_name' => $this->document->file_name,
            ]);
        }
    }

    /**
     * Handle a job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessPdfJob permanently failed", [
            'document_id' => $this->document->id,
            'file_name' => $this->document->file_name,
            'error' => $exception->getMessage(),
        ]);

        // Ensure document is marked as failed
        $this->document->markAsFailed($exception->getMessage());

        // Could send notification to uploader here
        // Notification::send($this->document->uploader, new PdfProcessingFailed($this->document));
    }

    /**
     * Get the tags that should be assigned to the job
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'pdf-processing',
            'document:' . $this->document->id,
        ];
    }
}
