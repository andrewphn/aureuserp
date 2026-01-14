<?php

namespace App\Services\Receipts;

use App\Services\Gmail\GmailAuthService;
use Google\Service\Gmail\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Receipt Email Fetcher
 *
 * Lists Gmail messages that match receipt filters and exposes attachment metadata.
 */
class ReceiptEmailFetcher
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/jpg',
    ];

    public function __construct(private readonly GmailAuthService $authService)
    {
    }

    /**
     * List messages that match receipt filters.
     */
    public function listReceiptMessages(?int $maxMessages = null): array
    {
        $gmail = $this->authService->getGmailService();

        if (! $gmail) {
            Log::warning('ReceiptEmailFetcher: Gmail service not configured');
            return [];
        }

        $userId = config('receipts.gmail.user_id', 'me');
        $limit = $maxMessages ?? (int) config('receipts.gmail.max_messages', 50);
        $query = $this->buildQuery();

        $results = [];

        try {
            $list = $gmail->users_messages->listUsersMessages($userId, [
                'q' => $query,
                'maxResults' => $limit,
                'includeSpamTrash' => false,
            ]);

            $messages = $list->getMessages() ?? [];

            foreach ($messages as $messageSummary) {
                $messageId = $messageSummary->getId();
                if (! $messageId) {
                    continue;
                }

                $message = $this->getMessage($messageId);
                if (! $message) {
                    continue;
                }

                $messageData = $this->buildMessageData($message);

                if (! $this->matchesFilters($messageData)) {
                    continue;
                }

                if (empty($messageData['attachments'])) {
                    continue;
                }

                $results[] = $messageData;
            }
        } catch (\Throwable $e) {
            Log::error('ReceiptEmailFetcher: Failed to list messages', [
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Download an attachment by message and attachment id.
     */
    public function downloadAttachment(string $messageId, string $attachmentId): ?string
    {
        $gmail = $this->authService->getGmailService();
        if (! $gmail) {
            return null;
        }

        try {
            $userId = config('receipts.gmail.user_id', 'me');
            $attachment = $gmail->users_messages_attachments->get($userId, $messageId, $attachmentId);
            $data = $attachment->getData();

            if (! $data) {
                return null;
            }

            $decoded = base64_decode(strtr($data, '-_', '+/'));

            return $decoded !== false ? $decoded : null;
        } catch (\Throwable $e) {
            Log::error('ReceiptEmailFetcher: Failed to download attachment', [
                'message_id' => $messageId,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the full Gmail message.
     */
    protected function getMessage(string $messageId): ?Message
    {
        $gmail = $this->authService->getGmailService();
        if (! $gmail) {
            return null;
        }

        try {
            $userId = config('receipts.gmail.user_id', 'me');
            return $gmail->users_messages->get($userId, $messageId, ['format' => 'full']);
        } catch (\Throwable $e) {
            Log::warning('ReceiptEmailFetcher: Failed to fetch message', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build a data array from a Gmail message.
     */
    protected function buildMessageData(Message $message): array
    {
        $payload = $message->getPayload();
        $headers = $payload?->getHeaders() ?? [];
        $fromHeader = $this->getHeaderValue($headers, 'From');
        $subject = $this->getHeaderValue($headers, 'Subject');
        $dateHeader = $this->getHeaderValue($headers, 'Date');

        return [
            'message_id' => $message->getId(),
            'thread_id' => $message->getThreadId(),
            'internal_date_ms' => $message->getInternalDate(),
            'from' => $fromHeader,
            'from_email' => $this->extractEmailAddress($fromHeader),
            'subject' => $subject,
            'date_header' => $dateHeader,
            'attachments' => $this->extractAttachments($payload),
        ];
    }

    /**
     * Extract attachments recursively from message payload.
     */
    protected function extractAttachments($payload): array
    {
        if (! $payload) {
            return [];
        }

        $attachments = [];

        $filename = $payload->getFilename();
        $body = $payload->getBody();
        $attachmentId = $body?->getAttachmentId();
        $mimeType = $payload->getMimeType();

        if ($filename && $attachmentId && in_array(strtolower($mimeType), self::ALLOWED_MIME_TYPES, true)) {
            $attachments[] = [
                'attachment_id' => $attachmentId,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size' => $body?->getSize(),
                'part_id' => $payload->getPartId(),
            ];
        }

        $parts = $payload->getParts() ?? [];
        foreach ($parts as $part) {
            $attachments = array_merge($attachments, $this->extractAttachments($part));
        }

        return $attachments;
    }

    /**
     * Determine if a message matches allowlist and keyword filters.
     */
    protected function matchesFilters(array $messageData): bool
    {
        $allowlist = array_map('strtolower', config('receipts.gmail.senders_allowlist', []));
        $keywords = array_map('strtolower', config('receipts.gmail.subject_keywords', []));

        $fromEmail = strtolower((string) ($messageData['from_email'] ?? ''));
        $subject = strtolower((string) ($messageData['subject'] ?? ''));

        if (! empty($allowlist) && ! in_array($fromEmail, $allowlist, true)) {
            return false;
        }

        if (! empty($keywords)) {
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($subject, $keyword)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Build Gmail search query based on config.
     */
    protected function buildQuery(): string
    {
        $queryParts = ['has:attachment'];

        $maxAgeDays = (int) config('receipts.gmail.max_age_days', 30);
        if ($maxAgeDays > 0) {
            $queryParts[] = 'newer_than:' . $maxAgeDays . 'd';
        }

        $allowlist = array_filter(config('receipts.gmail.senders_allowlist', []));
        if (! empty($allowlist)) {
            $fromTerms = array_map(
                fn ($sender) => 'from:' . $sender,
                $allowlist
            );
            $queryParts[] = '(' . implode(' OR ', $fromTerms) . ')';
        }

        $keywords = array_filter(config('receipts.gmail.subject_keywords', []));
        if (! empty($keywords)) {
            $subjectTerms = array_map(
                fn ($keyword) => 'subject:' . $keyword,
                $keywords
            );
            $queryParts[] = '(' . implode(' OR ', $subjectTerms) . ')';
        }

        return implode(' ', $queryParts);
    }

    /**
     * Get header value by name.
     */
    protected function getHeaderValue(array $headers, string $name): ?string
    {
        $header = Arr::first($headers, fn ($header) => strtolower($header->getName()) === strtolower($name));
        return $header?->getValue();
    }

    /**
     * Extract email address from a From header.
     */
    protected function extractEmailAddress(?string $fromHeader): string
    {
        if (! $fromHeader) {
            return '';
        }

        if (preg_match('/<([^>]+)>/', $fromHeader, $matches)) {
            return strtolower(trim($matches[1]));
        }

        return strtolower(trim($fromHeader));
    }
}
