<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

/**
 * RhinoExtractionJob - Track async cabinet extraction jobs from Rhino
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $project_id
 * @property int $user_id
 * @property string $status
 * @property array|null $options
 * @property string|null $rhino_document_name
 * @property string|null $rhino_document_path
 * @property array|null $rhino_metadata
 * @property array|null $results
 * @property int $cabinets_extracted
 * @property int $cabinets_imported
 * @property int $cabinets_pending_review
 * @property string|null $error_message
 * @property array|null $error_details
 * @property int $retry_count
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $duration_ms
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RhinoExtractionJob extends Model
{
    protected $fillable = [
        'uuid',
        'project_id',
        'user_id',
        'status',
        'options',
        'rhino_document_name',
        'rhino_document_path',
        'rhino_metadata',
        'results',
        'cabinets_extracted',
        'cabinets_imported',
        'cabinets_pending_review',
        'error_message',
        'error_details',
        'retry_count',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    protected $casts = [
        'options' => 'array',
        'rhino_metadata' => 'array',
        'results' => 'array',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cabinets_extracted' => 'integer',
        'cabinets_imported' => 'integer',
        'cabinets_pending_review' => 'integer',
        'retry_count' => 'integer',
        'duration_ms' => 'integer',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->uuid)) {
                $job->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Project relationship
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * User who initiated the extraction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Review items created from this job
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(RhinoExtractionReview::class, 'extraction_job_id');
    }

    /**
     * Mark job as started
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark job as completed
     */
    public function markAsCompleted(array $results = []): void
    {
        $duration = $this->started_at
            ? now()->diffInMilliseconds($this->started_at)
            : null;

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'duration_ms' => $duration,
            'results' => $results,
            'cabinets_extracted' => count($results['cabinets'] ?? []),
        ]);
    }

    /**
     * Mark job as failed
     */
    public function markAsFailed(string $message, array $details = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $message,
            'error_details' => $details,
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Check if job can be retried
     */
    public function canRetry(): bool
    {
        return $this->retry_count < 3 && $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if job is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Get summary for API response
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'project_id' => $this->project_id,
            'cabinets_extracted' => $this->cabinets_extracted,
            'cabinets_imported' => $this->cabinets_imported,
            'cabinets_pending_review' => $this->cabinets_pending_review,
            'duration_ms' => $this->duration_ms,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Scope: Pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Processing jobs
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope: Failed jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Completed jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: For project
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
