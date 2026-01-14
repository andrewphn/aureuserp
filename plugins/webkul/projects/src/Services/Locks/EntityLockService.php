<?php

namespace Webkul\Project\Services\Locks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Models\EntityLock;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\Project;

/**
 * Entity Lock Service
 *
 * Manages locking and unlocking of project entities.
 * Locks prevent direct edits to entities after certain gates pass.
 */
class EntityLockService
{
    /**
     * Entity types that can be locked after design lock.
     */
    protected array $designLockEntities = [
        'Cabinet',
        'CabinetSection',
        'Door',
        'Drawer',
        'Shelf',
        'Pullout',
    ];

    /**
     * Entity types that can be locked after procurement lock.
     */
    protected array $procurementLockEntities = [
        'BomLine',
    ];

    /**
     * Entity types that can be locked after production lock.
     */
    protected array $productionLockEntities = [
        'Cabinet',
        'CabinetSection',
        'Door',
        'Drawer',
    ];

    /**
     * Apply locks for a gate that just passed.
     *
     * @param Project $project
     * @param Gate $gate
     * @return array Created lock records
     */
    public function applyGateLocks(Project $project, Gate $gate): array
    {
        $locks = [];

        if ($gate->applies_design_lock) {
            $locks = array_merge($locks, $this->applyDesignLock($project, $gate));
        }

        if ($gate->applies_procurement_lock) {
            $locks = array_merge($locks, $this->applyProcurementLock($project, $gate));
        }

        if ($gate->applies_production_lock) {
            $locks = array_merge($locks, $this->applyProductionLock($project, $gate));
        }

        return $locks;
    }

    /**
     * Apply design lock to a project.
     */
    public function applyDesignLock(Project $project, Gate $gate): array
    {
        $locks = [];

        foreach ($this->designLockEntities as $entityType) {
            $lock = $this->createLock($project, $entityType, null, EntityLock::LEVEL_FULL, $gate->gate_key);
            if ($lock) {
                $locks[] = $lock;
            }
        }

        // Update project lock timestamp
        $project->update([
            'design_locked_at' => now(),
            'design_locked_by' => auth()->id(),
            'bom_snapshot_json' => $this->createBomSnapshot($project),
            'pricing_snapshot_json' => $this->createPricingSnapshot($project),
        ]);

        Log::info('Design lock applied', [
            'project_id' => $project->id,
            'gate_key' => $gate->gate_key,
            'locks_created' => count($locks),
        ]);

        return $locks;
    }

    /**
     * Apply procurement lock to a project.
     */
    public function applyProcurementLock(Project $project, Gate $gate): array
    {
        $locks = [];

        foreach ($this->procurementLockEntities as $entityType) {
            $lock = $this->createLock($project, $entityType, null, EntityLock::LEVEL_FULL, $gate->gate_key);
            if ($lock) {
                $locks[] = $lock;
            }
        }

        // Update project lock timestamp
        $project->update([
            'procurement_locked_at' => now(),
            'procurement_locked_by' => auth()->id(),
        ]);

        Log::info('Procurement lock applied', [
            'project_id' => $project->id,
            'gate_key' => $gate->gate_key,
            'locks_created' => count($locks),
        ]);

        return $locks;
    }

    /**
     * Apply production lock to a project.
     */
    public function applyProductionLock(Project $project, Gate $gate): array
    {
        $locks = [];

        foreach ($this->productionLockEntities as $entityType) {
            $lock = $this->createLock($project, $entityType, null, EntityLock::LEVEL_DIMENSIONS, $gate->gate_key);
            if ($lock) {
                $locks[] = $lock;
            }
        }

        // Update project lock timestamp
        $project->update([
            'production_locked_at' => now(),
            'production_locked_by' => auth()->id(),
        ]);

        Log::info('Production lock applied', [
            'project_id' => $project->id,
            'gate_key' => $gate->gate_key,
            'locks_created' => count($locks),
        ]);

        return $locks;
    }

    /**
     * Check if an entity is locked.
     *
     * @param Project $project
     * @param string $entityType
     * @param int|null $entityId
     * @param string|null $level
     * @return bool
     */
    public function isLocked(Project $project, string $entityType, ?int $entityId = null, ?string $level = null): bool
    {
        $query = EntityLock::active()
            ->forProject($project->id)
            ->where(function ($q) use ($entityType, $entityId) {
                $q->where('entity_type', $entityType)
                    ->where(function ($inner) use ($entityId) {
                        $inner->whereNull('entity_id') // Project-wide lock
                            ->orWhere('entity_id', $entityId);
                    });
            });

        if ($level) {
            $query->where(function ($q) use ($level) {
                $q->where('lock_level', $level)
                    ->orWhere('lock_level', EntityLock::LEVEL_FULL);
            });
        }

        return $query->exists();
    }

    /**
     * Check if a specific field on an entity is locked.
     *
     * @param Project $project
     * @param string $entityType
     * @param int|null $entityId
     * @param string $fieldName
     * @return bool
     */
    public function isFieldLocked(Project $project, string $entityType, ?int $entityId, string $fieldName): bool
    {
        $locks = EntityLock::active()
            ->forProject($project->id)
            ->where(function ($q) use ($entityType, $entityId) {
                $q->where('entity_type', $entityType)
                    ->where(function ($inner) use ($entityId) {
                        $inner->whereNull('entity_id')
                            ->orWhere('entity_id', $entityId);
                    });
            })
            ->get();

        foreach ($locks as $lock) {
            if ($lock->blocksField($fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get lock info for an entity.
     *
     * @param Project $project
     * @param string $entityType
     * @param int|null $entityId
     * @return EntityLock|null
     */
    public function getLockInfo(Project $project, string $entityType, ?int $entityId = null): ?EntityLock
    {
        return EntityLock::active()
            ->forProject($project->id)
            ->where('entity_type', $entityType)
            ->where(function ($q) use ($entityId) {
                $q->whereNull('entity_id')
                    ->orWhere('entity_id', $entityId);
            })
            ->orderBy('lock_level') // Full lock takes precedence
            ->first();
    }

    /**
     * Unlock entities via a change order.
     *
     * @param ChangeOrder $changeOrder
     * @return int Number of locks released
     */
    public function unlockForChangeOrder(ChangeOrder $changeOrder): int
    {
        $project = $changeOrder->project;
        $unlockGate = $changeOrder->unlocks_gate;

        $query = EntityLock::active()->forProject($project->id);

        if ($unlockGate) {
            $query->where('locked_by_gate', $unlockGate);
        }

        $locks = $query->get();

        foreach ($locks as $lock) {
            $lock->update([
                'unlock_change_order_id' => $changeOrder->id,
                'unlocked_at' => now(),
                'unlocked_by' => auth()->id(),
            ]);
        }

        Log::info('Locks released for change order', [
            'project_id' => $project->id,
            'change_order_id' => $changeOrder->id,
            'locks_released' => $locks->count(),
        ]);

        return $locks->count();
    }

    /**
     * Re-apply locks after a change order is applied.
     *
     * @param ChangeOrder $changeOrder
     * @return int Number of locks reapplied
     */
    public function relockAfterChangeOrder(ChangeOrder $changeOrder): int
    {
        $project = $changeOrder->project;
        $gate = Gate::findByKey($changeOrder->unlocks_gate);

        if (!$gate) {
            return 0;
        }

        $locks = $this->applyGateLocks($project, $gate);

        Log::info('Locks reapplied after change order', [
            'project_id' => $project->id,
            'change_order_id' => $changeOrder->id,
            'locks_created' => count($locks),
        ]);

        return count($locks);
    }

    /**
     * Create a lock record.
     */
    protected function createLock(
        Project $project,
        string $entityType,
        ?int $entityId,
        string $level,
        string $gateKey
    ): ?EntityLock {
        // Check if lock already exists
        $existing = EntityLock::active()
            ->forProject($project->id)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('lock_level', $level)
            ->first();

        if ($existing) {
            return null;
        }

        return EntityLock::create([
            'project_id' => $project->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'lock_level' => $level,
            'locked_by_gate' => $gateKey,
            'locked_at' => now(),
            'locked_by' => auth()->id(),
        ]);
    }

    /**
     * Create a BOM snapshot for the project.
     */
    protected function createBomSnapshot(Project $project): array
    {
        $bomLines = $project->bomLines ?? collect();

        return $bomLines->map(function ($line) {
            return [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'component_name' => $line->component_name,
                'quantity_required' => $line->quantity_required,
                'unit_of_measure' => $line->unit_of_measure,
                'total_material_cost' => $line->total_material_cost,
            ];
        })->toArray();
    }

    /**
     * Create a pricing snapshot for the project.
     */
    protected function createPricingSnapshot(Project $project): array
    {
        $rooms = $project->rooms;

        $totalEstimate = $rooms->sum('estimated_project_value');
        $quotedPrice = $rooms->sum('quoted_price');

        return [
            'total_estimate' => $totalEstimate,
            'quoted_price' => $quotedPrice,
            'rooms' => $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'estimated_value' => $room->estimated_project_value,
                    'quoted_price' => $room->quoted_price,
                    'total_linear_feet' => $room->total_linear_feet_tier_1 
                        + $room->total_linear_feet_tier_2 
                        + $room->total_linear_feet_tier_3 
                        + $room->total_linear_feet_tier_4 
                        + $room->total_linear_feet_tier_5,
                ];
            })->toArray(),
            'snapshot_at' => now()->toIso8601String(),
        ];
    }
}
