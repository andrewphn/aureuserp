<?php

namespace Webkul\Project\Traits;

use Webkul\Project\Models\EntityLock;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Locks\EntityLockService;
use Webkul\Project\Services\Locks\LockViolationException;

/**
 * Trait for models that can be locked after certain gates pass.
 *
 * This trait:
 * - Prevents saves when the entity is locked
 * - Provides helper methods to check lock status
 * - Integrates with the Change Order workflow
 *
 * @property-read Project|null $project The project this entity belongs to
 */
trait HasEntityLock
{
    /**
     * Fields that are always editable regardless of lock status.
     * Override in model to customize.
     */
    protected static array $lockExemptFields = [
        'updated_at',
        'qc_passed',
        'qc_notes',
        'qc_completed_at',
        'notes',
    ];

    /**
     * Boot the trait.
     */
    public static function bootHasEntityLock(): void
    {
        static::updating(function ($model) {
            $model->validateLockBeforeSave();
        });
    }

    /**
     * Validate lock status before saving.
     *
     * @throws LockViolationException
     */
    protected function validateLockBeforeSave(): void
    {
        // Skip if bypassing lock check (for system operations)
        if ($this->shouldBypassLockCheck()) {
            return;
        }

        $project = $this->getProjectForLockCheck();
        if (!$project) {
            return;
        }

        $lockService = app(EntityLockService::class);
        $entityType = $this->getLockEntityType();
        $entityId = $this->getKey();

        // Check if entity is locked
        $lock = $lockService->getLockInfo($project, $entityType, $entityId);
        if (!$lock || !$lock->isActive()) {
            return;
        }

        // Check which fields are being modified
        $dirtyFields = array_keys($this->getDirty());
        $lockedFields = array_filter($dirtyFields, function ($field) use ($lock) {
            // Allow exempt fields
            if (in_array($field, static::$lockExemptFields)) {
                return false;
            }
            // Check if this specific field is blocked by the lock level
            return $lock->blocksField($field);
        });

        if (!empty($lockedFields)) {
            throw new LockViolationException(
                $lock,
                $entityType,
                $entityId,
                implode(', ', $lockedFields)
            );
        }
    }

    /**
     * Get the project for lock checking.
     * Override in model if project is accessed differently.
     */
    protected function getProjectForLockCheck(): ?Project
    {
        // Try direct project relationship
        if (method_exists($this, 'project') && $this->project) {
            return $this->project;
        }

        // Try through cabinet
        if (method_exists($this, 'cabinet') && $this->cabinet?->project) {
            return $this->cabinet->project;
        }

        // Try through cabinet run
        if (method_exists($this, 'cabinetRun') && $this->cabinetRun?->project) {
            return $this->cabinetRun->project;
        }

        // Try to get project_id directly
        if (isset($this->project_id)) {
            return Project::find($this->project_id);
        }

        return null;
    }

    /**
     * Get the entity type name for lock checking.
     */
    protected function getLockEntityType(): string
    {
        return class_basename($this);
    }

    /**
     * Check if lock checking should be bypassed.
     * Can be used for system operations that need to modify locked entities.
     */
    protected function shouldBypassLockCheck(): bool
    {
        return $this->bypassLockCheck ?? false;
    }

    /**
     * Temporarily bypass lock check for a callback.
     *
     * @param callable $callback
     * @return mixed
     */
    public function withoutLockCheck(callable $callback)
    {
        $this->bypassLockCheck = true;
        try {
            return $callback($this);
        } finally {
            $this->bypassLockCheck = false;
        }
    }

    /**
     * Check if this entity is currently locked.
     *
     * @param string|null $level Optional lock level to check
     * @return bool
     */
    public function isLocked(?string $level = null): bool
    {
        $project = $this->getProjectForLockCheck();
        if (!$project) {
            return false;
        }

        return app(EntityLockService::class)->isLocked(
            $project,
            $this->getLockEntityType(),
            $this->getKey(),
            $level
        );
    }

    /**
     * Check if a specific field is locked.
     *
     * @param string $fieldName
     * @return bool
     */
    public function isFieldLocked(string $fieldName): bool
    {
        $project = $this->getProjectForLockCheck();
        if (!$project) {
            return false;
        }

        return app(EntityLockService::class)->isFieldLocked(
            $project,
            $this->getLockEntityType(),
            $this->getKey(),
            $fieldName
        );
    }

    /**
     * Get lock information for this entity.
     *
     * @return EntityLock|null
     */
    public function getLockInfo(): ?EntityLock
    {
        $project = $this->getProjectForLockCheck();
        if (!$project) {
            return null;
        }

        return app(EntityLockService::class)->getLockInfo(
            $project,
            $this->getLockEntityType(),
            $this->getKey()
        );
    }

    /**
     * Check if editing this entity requires a change order.
     *
     * @return bool
     */
    public function requiresChangeOrderToEdit(): bool
    {
        return $this->isLocked();
    }

    /**
     * Get the lock status as an array for UI display.
     *
     * @return array
     */
    public function getLockStatusForDisplay(): array
    {
        $lock = $this->getLockInfo();

        if (!$lock) {
            return [
                'is_locked' => false,
                'lock_level' => null,
                'locked_by_gate' => null,
                'locked_at' => null,
                'locked_by' => null,
                'message' => 'Not locked',
            ];
        }

        return [
            'is_locked' => true,
            'lock_level' => $lock->lock_level,
            'locked_by_gate' => $lock->locked_by_gate,
            'locked_at' => $lock->locked_at,
            'locked_by' => $lock->lockedByUser?->name,
            'message' => "Locked by {$lock->locked_by_gate} gate. Create a Change Order to modify.",
        ];
    }
}
