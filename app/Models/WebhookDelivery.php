<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook Delivery log model
 *
 * @property int $id
 * @property int $webhook_subscription_id
 * @property string $event
 * @property array $payload
 * @property int|null $response_status
 * @property string|null $response_body
 * @property int|null $duration_ms
 * @property string $status
 * @property int $attempts
 * @property \Carbon\Carbon|null $next_retry_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_subscription_id',
        'event',
        'payload',
        'response_status',
        'response_body',
        'duration_ms',
        'status',
        'attempts',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_status' => 'integer',
        'duration_ms' => 'integer',
        'attempts' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    public const MAX_ATTEMPTS = 5;

    /**
     * Webhook subscription this delivery belongs to
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'webhook_subscription_id');
    }

    /**
     * Mark delivery as successful
     */
    public function markSuccess(int $responseStatus, ?string $responseBody, int $durationMs): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_status' => $responseStatus,
            'response_body' => $responseBody ? substr($responseBody, 0, 5000) : null,
            'duration_ms' => $durationMs,
            'attempts' => $this->attempts + 1,
        ]);

        $this->subscription->markSuccess();
    }

    /**
     * Mark delivery as failed
     */
    public function markFailed(int $responseStatus, ?string $responseBody, int $durationMs): void
    {
        $attempts = $this->attempts + 1;
        $shouldRetry = $attempts < self::MAX_ATTEMPTS;

        $this->update([
            'status' => $shouldRetry ? self::STATUS_RETRYING : self::STATUS_FAILED,
            'response_status' => $responseStatus,
            'response_body' => $responseBody ? substr($responseBody, 0, 5000) : null,
            'duration_ms' => $durationMs,
            'attempts' => $attempts,
            'next_retry_at' => $shouldRetry ? $this->calculateNextRetry($attempts) : null,
        ]);

        if (!$shouldRetry) {
            $this->subscription->markFailure("HTTP {$responseStatus}: " . substr($responseBody ?? 'No response', 0, 200));
        }
    }

    /**
     * Calculate next retry time with exponential backoff
     */
    protected function calculateNextRetry(int $attempts): \Carbon\Carbon
    {
        // Exponential backoff: 1min, 5min, 15min, 60min
        $delays = [60, 300, 900, 3600];
        $delay = $delays[$attempts - 1] ?? 3600;

        return now()->addSeconds($delay);
    }

    /**
     * Scope: Pending deliveries
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Ready for retry
     */
    public function scopeReadyForRetry($query)
    {
        return $query->where('status', self::STATUS_RETRYING)
            ->where('next_retry_at', '<=', now());
    }
}
