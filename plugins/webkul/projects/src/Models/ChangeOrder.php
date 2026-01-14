<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Sale\Models\Order as SalesOrder;
use Webkul\Security\Models\User;

/**
 * Change Order Eloquent model
 *
 * Change orders track scope changes to locked projects.
 * They are the only legal path to modify locked data.
 *
 * @property int $id
 * @property int $project_id
 * @property string $change_order_number
 * @property string $title
 * @property string|null $description
 * @property string $reason
 * @property string $status
 * @property int|null $requested_by
 * @property \Carbon\Carbon|null $requested_at
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property string|null $approval_notes
 * @property int|null $rejected_by
 * @property \Carbon\Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property int|null $applied_by
 * @property \Carbon\Carbon|null $applied_at
 * @property float $price_delta
 * @property float $labor_hours_delta
 * @property array|null $bom_delta_json
 * @property string|null $affected_stage
 * @property string|null $unlocks_gate
 * @property int|null $sales_order_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection|ChangeOrderLine[] $lines
 */
class ChangeOrder extends Model
{
    use HasFactory;

    /**
     * Table name.
     */
    protected $table = 'projects_change_orders';

    /**
     * Status constants.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Reason constants.
     */
    public const REASON_CLIENT_REQUEST = 'client_request';
    public const REASON_FIELD_CONDITION = 'field_condition';
    public const REASON_DESIGN_ERROR = 'design_error';
    public const REASON_MATERIAL_SUBSTITUTION = 'material_substitution';
    public const REASON_SCOPE_ADDITION = 'scope_addition';
    public const REASON_SCOPE_REMOVAL = 'scope_removal';
    public const REASON_OTHER = 'other';

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'project_id',
        'change_order_number',
        'title',
        'description',
        'reason',
        'status',
        'requested_by',
        'requested_at',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'applied_by',
        'applied_at',
        'price_delta',
        'labor_hours_delta',
        'bom_delta_json',
        'affected_stage',
        'unlocks_gate',
        'sales_order_id',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'applied_at' => 'datetime',
        'price_delta' => 'decimal:2',
        'labor_hours_delta' => 'decimal:2',
        'bom_delta_json' => 'array',
    ];

    /**
     * Boot method - auto-generate change order number.
     */
    protected static function booted()
    {
        static::creating(function ($changeOrder) {
            if (empty($changeOrder->change_order_number)) {
                $changeOrder->change_order_number = static::generateNumber($changeOrder->project_id);
            }
            if (empty($changeOrder->requested_at)) {
                $changeOrder->requested_at = now();
            }
            if (empty($changeOrder->requested_by)) {
                $changeOrder->requested_by = auth()->id();
            }
        });
    }

    /**
     * Generate next change order number for a project.
     */
    public static function generateNumber(int $projectId): string
    {
        $count = static::where('project_id', $projectId)->count() + 1;
        return sprintf('CO-%03d', $count);
    }

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the change order lines.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ChangeOrderLine::class, 'change_order_id');
    }

    /**
     * Get the user who requested.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected.
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user who applied.
     */
    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Get the sales order for billing.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    /**
     * Get entity locks that were unlocked by this change order.
     */
    public function unlockedEntities(): HasMany
    {
        return $this->hasMany(EntityLock::class, 'unlock_change_order_id');
    }

    /**
     * Check if change order can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if change order can be applied.
     */
    public function canBeApplied(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if change order can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL]);
    }

    /**
     * Check if change order is complete (applied or cancelled).
     */
    public function isComplete(): bool
    {
        return in_array($this->status, [self::STATUS_APPLIED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope to get pending change orders.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_PENDING_APPROVAL]);
    }

    /**
     * Scope to filter by project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_APPLIED => 'Applied',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get available reasons.
     */
    public static function getReasons(): array
    {
        return [
            self::REASON_CLIENT_REQUEST => 'Client Request',
            self::REASON_FIELD_CONDITION => 'Field Condition',
            self::REASON_DESIGN_ERROR => 'Design Error',
            self::REASON_MATERIAL_SUBSTITUTION => 'Material Substitution',
            self::REASON_SCOPE_ADDITION => 'Scope Addition',
            self::REASON_SCOPE_REMOVAL => 'Scope Removal',
            self::REASON_OTHER => 'Other',
        ];
    }
}
