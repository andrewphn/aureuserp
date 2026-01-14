<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Models\Operation;
use Webkul\Partner\Models\Partner;
use Webkul\Purchase\Models\Order as PurchaseOrder;
use Webkul\Security\Models\User;

/**
 * DocumentScanLog Model
 *
 * Tracks AI document scanning attempts for audit and review purposes.
 * Each scan creates a log entry with confidence scores, matching results,
 * and review status.
 *
 * @property int $id
 * @property int|null $operation_id
 * @property string $document_type
 * @property string|null $file_path
 * @property string|null $original_filename
 * @property int|null $file_size
 * @property array|null $raw_ai_response
 * @property array|null $extracted_data
 * @property float|null $overall_confidence
 * @property float|null $vendor_confidence
 * @property float|null $po_confidence
 * @property bool $vendor_matched
 * @property int|null $matched_vendor_id
 * @property bool $po_matched
 * @property int|null $matched_po_id
 * @property int $lines_total_count
 * @property int $lines_matched_count
 * @property int $lines_unmatched_count
 * @property string $status
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon|null $reviewed_at
 * @property string|null $review_notes
 * @property int|null $processing_time_ms
 * @property string|null $error_message
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DocumentScanLog extends Model
{
    /**
     * Status constants
     */
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_AUTO_APPLIED = 'auto_applied';

    /**
     * Document type constants
     */
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_PACKING_SLIP = 'packing_slip';
    public const TYPE_PRODUCT_LABEL = 'product_label';
    public const TYPE_QUOTE = 'quote';

    /**
     * The table associated with the model.
     */
    protected $table = 'document_scan_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'operation_id',
        'document_type',
        'file_path',
        'original_filename',
        'file_size',
        'raw_ai_response',
        'extracted_data',
        'overall_confidence',
        'vendor_confidence',
        'po_confidence',
        'vendor_matched',
        'matched_vendor_id',
        'po_matched',
        'matched_po_id',
        'lines_total_count',
        'lines_matched_count',
        'lines_unmatched_count',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'processing_time_ms',
        'error_message',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'raw_ai_response' => 'array',
        'extracted_data' => 'array',
        'overall_confidence' => 'decimal:2',
        'vendor_confidence' => 'decimal:2',
        'po_confidence' => 'decimal:2',
        'vendor_matched' => 'boolean',
        'po_matched' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the inventory operation associated with this scan.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    /**
     * Get the matched vendor.
     */
    public function matchedVendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'matched_vendor_id');
    }

    /**
     * Get the matched purchase order.
     */
    public function matchedPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'matched_po_id');
    }

    /**
     * Get the user who reviewed this scan.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the user who created this scan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the scan needs review.
     */
    public function needsReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    /**
     * Check if the scan was successful (high confidence).
     */
    public function isHighConfidence(float $threshold = 0.8): bool
    {
        return $this->overall_confidence !== null && $this->overall_confidence >= $threshold;
    }

    /**
     * Get the match rate as a percentage.
     */
    public function getMatchRateAttribute(): float
    {
        if ($this->lines_total_count === 0) {
            return 0;
        }

        return round(($this->lines_matched_count / $this->lines_total_count) * 100, 1);
    }

    /**
     * Mark the scan as approved.
     */
    public function approve(?int $reviewerId = null, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId ?? auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Mark the scan as rejected.
     */
    public function reject(?int $reviewerId = null, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewerId ?? auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Scope for pending reviews.
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    /**
     * Scope for scans by document type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope for high confidence scans.
     */
    public function scopeHighConfidence($query, float $threshold = 0.8)
    {
        return $query->where('overall_confidence', '>=', $threshold);
    }

    /**
     * Scope for low confidence scans.
     */
    public function scopeLowConfidence($query, float $threshold = 0.5)
    {
        return $query->where('overall_confidence', '<', $threshold);
    }
}
