<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

/**
 * Entity Lock Eloquent model
 *
 * Tracks which project entities are locked from editing.
 * Locks can be at different levels and can be temporarily unlocked via change orders.
 *
 * @property int $id
 * @property int $project_id
 * @property string $entity_type
 * @property int|null $entity_id
 * @property string $lock_level
 * @property string $locked_by_gate
 * @property \Carbon\Carbon $locked_at
 * @property int|null $locked_by
 * @property int|null $unlock_change_order_id
 * @property \Carbon\Carbon|null $unlocked_at
 * @property int|null $unlocked_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Project $project
 * @property-read User|null $lockedByUser
 * @property-read User|null $unlockedByUser
 * @property-read ChangeOrder|null $unlockChangeOrder
 */
class EntityLock extends Model
{
    use HasFactory;

    /**
     * Table name.
     */
    protected $table = 'projects_entity_locks';

    /**
     * Lock level constants.
     */
    public const LEVEL_FULL = 'full';
    public const LEVEL_DIMENSIONS = 'dimensions';
    public const LEVEL_MATERIALS = 'materials';
    public const LEVEL_PRICING = 'pricing';

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'project_id',
        'entity_type',
        'entity_id',
        'lock_level',
        'locked_by_gate',
        'locked_at',
        'locked_by',
        'unlock_change_order_id',
        'unlocked_at',
        'unlocked_by',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'locked_at' => 'datetime',
        'unlocked_at' => 'datetime',
    ];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the user who created the lock.
     */
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Get the user who unlocked.
     */
    public function unlockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    /**
     * Get the change order that unlocked this.
     */
    public function unlockChangeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class, 'unlock_change_order_id');
    }

    /**
     * Check if lock is currently active.
     */
    public function isActive(): bool
    {
        return $this->unlocked_at === null;
    }

    /**
     * Scope to get only active locks.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('unlocked_at');
    }

    /**
     * Scope to filter by entity type.
     */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to filter by entity.
     */
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where(function ($q) use ($entityId) {
                $q->where('entity_id', $entityId)
                    ->orWhereNull('entity_id'); // Also match project-wide locks
            });
    }

    /**
     * Scope to filter by lock level.
     */
    public function scopeForLevel($query, string $level)
    {
        return $query->where('lock_level', $level);
    }

    /**
     * Scope to filter by project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Get available lock levels.
     */
    public static function getLockLevels(): array
    {
        return [
            self::LEVEL_FULL => 'Full Lock (all fields)',
            self::LEVEL_DIMENSIONS => 'Dimensions Only',
            self::LEVEL_MATERIALS => 'Materials Only',
            self::LEVEL_PRICING => 'Pricing Only',
        ];
    }

    /**
     * Check if this lock blocks a specific field.
     */
    public function blocksField(string $fieldName): bool
    {
        if ($this->lock_level === self::LEVEL_FULL) {
            return true;
        }

        $dimensionFields = ['width', 'height', 'depth', 'length', 'thickness', '_inches'];
        $materialFields = ['material', 'wood', 'species', 'finish', 'product_id'];
        $pricingFields = ['price', 'cost', 'rate', 'amount'];

        return match ($this->lock_level) {
            self::LEVEL_DIMENSIONS => $this->fieldMatchesAny($fieldName, $dimensionFields),
            self::LEVEL_MATERIALS => $this->fieldMatchesAny($fieldName, $materialFields),
            self::LEVEL_PRICING => $this->fieldMatchesAny($fieldName, $pricingFields),
            default => false,
        };
    }

    /**
     * Check if a field name matches any pattern.
     */
    protected function fieldMatchesAny(string $fieldName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains(strtolower($fieldName), strtolower($pattern))) {
                return true;
            }
        }
        return false;
    }
}
