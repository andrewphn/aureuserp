<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

/**
 * RhinoExtractionReview - Review queue for low-confidence extractions and sync conflicts
 *
 * @property int $id
 * @property string $uuid
 * @property int $extraction_job_id
 * @property int|null $project_id
 * @property int|null $cabinet_id
 * @property string|null $rhino_group_name
 * @property string|null $cabinet_number
 * @property array $extraction_data
 * @property array|null $ai_interpretation
 * @property float $confidence_score
 * @property string $status
 * @property string $review_type
 * @property int|null $reviewer_id
 * @property array|null $reviewer_corrections
 * @property string|null $reviewer_notes
 * @property \Carbon\Carbon|null $reviewed_at
 * @property array|null $erp_data
 * @property array|null $rhino_data
 * @property string|null $sync_direction
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RhinoExtractionReview extends Model
{
    protected $fillable = [
        'uuid',
        'extraction_job_id',
        'project_id',
        'cabinet_id',
        'rhino_group_name',
        'cabinet_number',
        'extraction_data',
        'ai_interpretation',
        'confidence_score',
        'status',
        'review_type',
        'reviewer_id',
        'reviewer_corrections',
        'reviewer_notes',
        'reviewed_at',
        'erp_data',
        'rhino_data',
        'sync_direction',
    ];

    protected $casts = [
        'extraction_data' => 'array',
        'ai_interpretation' => 'array',
        'confidence_score' => 'decimal:2',
        'reviewer_corrections' => 'array',
        'reviewed_at' => 'datetime',
        'erp_data' => 'array',
        'rhino_data' => 'array',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_AUTO_APPROVED = 'auto_approved';

    /**
     * Review type constants
     */
    public const TYPE_LOW_CONFIDENCE = 'low_confidence';
    public const TYPE_DIMENSION_MISMATCH = 'dimension_mismatch';
    public const TYPE_SYNC_CONFLICT = 'sync_conflict';
    public const TYPE_MISSING_DATA = 'missing_data';

    /**
     * Confidence thresholds
     */
    public const THRESHOLD_HIGH = 80;
    public const THRESHOLD_MEDIUM = 50;

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($review) {
            if (empty($review->uuid)) {
                $review->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Extraction job relationship
     */
    public function extractionJob(): BelongsTo
    {
        return $this->belongsTo(RhinoExtractionJob::class, 'extraction_job_id');
    }

    /**
     * Project relationship
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Cabinet relationship (if linked)
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }

    /**
     * Reviewer relationship
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Approve the review item
     */
    public function approve(int $reviewerId, array $corrections = [], ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewer_id' => $reviewerId,
            'reviewer_corrections' => $corrections,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Reject the review item
     */
    public function reject(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewer_id' => $reviewerId,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Auto-approve based on high confidence
     */
    public function autoApprove(): void
    {
        $this->update([
            'status' => self::STATUS_AUTO_APPROVED,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Check if review is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if review is approved (manual or auto)
     */
    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_AUTO_APPROVED]);
    }

    /**
     * Get confidence level (high, medium, low)
     */
    public function getConfidenceLevel(): string
    {
        if ($this->confidence_score >= self::THRESHOLD_HIGH) {
            return 'high';
        }
        if ($this->confidence_score >= self::THRESHOLD_MEDIUM) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Get display color for confidence
     */
    public function getConfidenceColor(): string
    {
        return match ($this->getConfidenceLevel()) {
            'high' => 'success',
            'medium' => 'warning',
            'low' => 'danger',
        };
    }

    /**
     * Get merged data (extraction + corrections)
     */
    public function getMergedData(): array
    {
        $data = $this->extraction_data ?? [];

        if (!empty($this->reviewer_corrections)) {
            $data = array_merge($data, $this->reviewer_corrections);
        }

        return $data;
    }

    /**
     * Get summary for API response
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'cabinet_number' => $this->cabinet_number,
            'rhino_group_name' => $this->rhino_group_name,
            'confidence_score' => $this->confidence_score,
            'confidence_level' => $this->getConfidenceLevel(),
            'status' => $this->status,
            'review_type' => $this->review_type,
            'extraction_data' => $this->extraction_data,
            'ai_interpretation' => $this->ai_interpretation,
            'reviewer_corrections' => $this->reviewer_corrections,
            'created_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
        ];
    }

    /**
     * Scope: Pending reviews
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_AUTO_APPROVED]);
    }

    /**
     * Scope: Low confidence
     */
    public function scopeLowConfidence($query)
    {
        return $query->where('confidence_score', '<', self::THRESHOLD_MEDIUM);
    }

    /**
     * Scope: Medium confidence
     */
    public function scopeMediumConfidence($query)
    {
        return $query->whereBetween('confidence_score', [self::THRESHOLD_MEDIUM, self::THRESHOLD_HIGH - 0.01]);
    }

    /**
     * Scope: High confidence
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', self::THRESHOLD_HIGH);
    }

    /**
     * Scope: Sync conflicts only
     */
    public function scopeSyncConflicts($query)
    {
        return $query->where('review_type', self::TYPE_SYNC_CONFLICT);
    }

    /**
     * Scope: For project
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope: For extraction job
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('extraction_job_id', $jobId);
    }
}
