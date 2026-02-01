<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

/**
 * Change Order Stop Action Model
 *
 * Tracks all stop actions taken when a change order is approved.
 * This provides a complete audit trail of blocked tasks, held purchase orders,
 * and notifications sent, allowing proper reversal when the change order
 * is applied or cancelled.
 *
 * @property int $id
 * @property int $change_order_id
 * @property string $action_type
 * @property string $entity_type
 * @property int $entity_id
 * @property string|null $previous_state
 * @property string|null $new_state
 * @property int|null $performed_by
 * @property \Carbon\Carbon|null $performed_at
 * @property int|null $reverted_by
 * @property \Carbon\Carbon|null $reverted_at
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read ChangeOrder $changeOrder
 * @property-read User|null $performedByUser
 * @property-read User|null $revertedByUser
 */
class ChangeOrderStopAction extends Model
{
    /**
     * Table name.
     */
    protected $table = 'projects_change_order_stop_actions';

    /**
     * Action type constants.
     */
    public const TYPE_TASK_BLOCKED = 'task_blocked';
    public const TYPE_PO_HELD = 'po_held';
    public const TYPE_DELIVERY_BLOCKED = 'delivery_blocked';
    public const TYPE_NOTIFICATION_SENT = 'notification_sent';

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'change_order_id',
        'action_type',
        'entity_type',
        'entity_id',
        'previous_state',
        'new_state',
        'performed_by',
        'performed_at',
        'reverted_by',
        'reverted_at',
        'metadata',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'performed_at' => 'datetime',
        'reverted_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the change order that triggered this action.
     */
    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class, 'change_order_id');
    }

    /**
     * Get the user who performed this action.
     */
    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the user who reverted this action.
     */
    public function revertedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    /**
     * Scope to get active (non-reverted) stop actions.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('reverted_at');
    }

    /**
     * Scope to get reverted stop actions.
     */
    public function scopeReverted($query)
    {
        return $query->whereNotNull('reverted_at');
    }

    /**
     * Scope to filter by change order.
     */
    public function scopeForChangeOrder($query, int $changeOrderId)
    {
        return $query->where('change_order_id', $changeOrderId);
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeOfType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope to filter by entity.
     */
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /**
     * Check if this action has been reverted.
     */
    public function isReverted(): bool
    {
        return $this->reverted_at !== null;
    }

    /**
     * Check if this action is still active.
     */
    public function isActive(): bool
    {
        return $this->reverted_at === null;
    }

    /**
     * Mark this action as reverted.
     */
    public function markReverted(?int $userId = null): bool
    {
        return $this->update([
            'reverted_by' => $userId ?? auth()->id(),
            'reverted_at' => now(),
        ]);
    }

    /**
     * Get available action types.
     */
    public static function getActionTypes(): array
    {
        return [
            self::TYPE_TASK_BLOCKED => 'Task Blocked',
            self::TYPE_PO_HELD => 'Purchase Order Held',
            self::TYPE_DELIVERY_BLOCKED => 'Delivery Blocked',
            self::TYPE_NOTIFICATION_SENT => 'Notification Sent',
        ];
    }
}
