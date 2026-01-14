<?php

namespace Webkul\Project\Services\ChangeOrders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Events\ChangeOrderApplied;
use Webkul\Project\Events\ChangeOrderApproved;
use Webkul\Project\Events\ChangeOrderCreated;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Models\ChangeOrderLine;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Locks\EntityLockService;

/**
 * Change Order Service
 *
 * Manages the full lifecycle of change orders:
 * - Creating change orders with line items
 * - Calculating price and BOM impact
 * - Approving/rejecting change orders
 * - Applying changes to locked entities
 */
class ChangeOrderService
{
    protected EntityLockService $lockService;

    public function __construct(EntityLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    /**
     * Create a new change order.
     *
     * @param Project $project
     * @param array $data
     * @return ChangeOrder
     */
    public function create(Project $project, array $data): ChangeOrder
    {
        $changeOrder = DB::transaction(function () use ($project, $data) {
            $changeOrder = ChangeOrder::create([
                'project_id' => $project->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'reason' => $data['reason'] ?? ChangeOrder::REASON_CLIENT_REQUEST,
                'status' => ChangeOrder::STATUS_DRAFT,
                'affected_stage' => $data['affected_stage'] ?? $project->stage?->stage_key,
                'unlocks_gate' => $data['unlocks_gate'] ?? null,
            ]);

            // Add line items if provided
            if (!empty($data['lines'])) {
                foreach ($data['lines'] as $lineData) {
                    $this->addLine($changeOrder, $lineData);
                }
            }

            // Calculate totals
            $this->calculateTotals($changeOrder);

            return $changeOrder;
        });

        Log::info('Change order created', [
            'change_order_id' => $changeOrder->id,
            'project_id' => $project->id,
            'title' => $changeOrder->title,
        ]);

        event(new ChangeOrderCreated($changeOrder));

        return $changeOrder;
    }

    /**
     * Add a line item to a change order.
     *
     * @param ChangeOrder $changeOrder
     * @param array $data
     * @return ChangeOrderLine
     */
    public function addLine(ChangeOrder $changeOrder, array $data): ChangeOrderLine
    {
        // Get current value from entity
        $entity = $this->getEntity($data['entity_type'], $data['entity_id']);
        $oldValue = $entity ? $entity->{$data['field_name']} : null;

        $line = ChangeOrderLine::create([
            'change_order_id' => $changeOrder->id,
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'],
            'field_name' => $data['field_name'],
            'old_value' => $oldValue,
            'new_value' => $data['new_value'],
            'price_impact' => $data['price_impact'] ?? 0,
            'bom_impact_json' => $data['bom_impact_json'] ?? null,
        ]);

        // Recalculate totals
        $this->calculateTotals($changeOrder);

        return $line;
    }

    /**
     * Submit change order for approval.
     *
     * @param ChangeOrder $changeOrder
     * @return ChangeOrder
     */
    public function submitForApproval(ChangeOrder $changeOrder): ChangeOrder
    {
        if ($changeOrder->status !== ChangeOrder::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Only draft change orders can be submitted for approval');
        }

        $changeOrder->update([
            'status' => ChangeOrder::STATUS_PENDING_APPROVAL,
        ]);

        Log::info('Change order submitted for approval', [
            'change_order_id' => $changeOrder->id,
        ]);

        return $changeOrder->fresh();
    }

    /**
     * Approve a change order.
     *
     * @param ChangeOrder $changeOrder
     * @param string|null $notes
     * @return ChangeOrder
     */
    public function approve(ChangeOrder $changeOrder, ?string $notes = null): ChangeOrder
    {
        if (!$changeOrder->canBeApproved()) {
            throw new \InvalidArgumentException('Change order cannot be approved in current status');
        }

        $changeOrder = DB::transaction(function () use ($changeOrder, $notes) {
            $changeOrder->update([
                'status' => ChangeOrder::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            // Unlock entities for editing
            $this->lockService->unlockForChangeOrder($changeOrder);

            return $changeOrder;
        });

        Log::info('Change order approved', [
            'change_order_id' => $changeOrder->id,
            'approved_by' => auth()->id(),
        ]);

        event(new ChangeOrderApproved($changeOrder));

        return $changeOrder->fresh();
    }

    /**
     * Reject a change order.
     *
     * @param ChangeOrder $changeOrder
     * @param string $reason
     * @return ChangeOrder
     */
    public function reject(ChangeOrder $changeOrder, string $reason): ChangeOrder
    {
        if ($changeOrder->status !== ChangeOrder::STATUS_PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Only pending change orders can be rejected');
        }

        $changeOrder->update([
            'status' => ChangeOrder::STATUS_REJECTED,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        Log::info('Change order rejected', [
            'change_order_id' => $changeOrder->id,
            'rejected_by' => auth()->id(),
            'reason' => $reason,
        ]);

        return $changeOrder->fresh();
    }

    /**
     * Apply a change order, modifying the locked entities.
     *
     * @param ChangeOrder $changeOrder
     * @return ChangeOrder
     */
    public function apply(ChangeOrder $changeOrder): ChangeOrder
    {
        if (!$changeOrder->canBeApplied()) {
            throw new \InvalidArgumentException('Change order must be approved before applying');
        }

        $changeOrder = DB::transaction(function () use ($changeOrder) {
            // Apply each line item
            foreach ($changeOrder->lines()->unapplied()->get() as $line) {
                $this->applyLine($line);
            }

            // Re-lock entities
            $this->lockService->relockAfterChangeOrder($changeOrder);

            // Update change order status
            $changeOrder->update([
                'status' => ChangeOrder::STATUS_APPLIED,
                'applied_by' => auth()->id(),
                'applied_at' => now(),
            ]);

            return $changeOrder;
        });

        Log::info('Change order applied', [
            'change_order_id' => $changeOrder->id,
            'applied_by' => auth()->id(),
            'lines_applied' => $changeOrder->lines()->count(),
        ]);

        event(new ChangeOrderApplied($changeOrder));

        return $changeOrder->fresh();
    }

    /**
     * Cancel a change order.
     *
     * @param ChangeOrder $changeOrder
     * @return ChangeOrder
     */
    public function cancel(ChangeOrder $changeOrder): ChangeOrder
    {
        if ($changeOrder->isComplete()) {
            throw new \InvalidArgumentException('Cannot cancel a completed change order');
        }

        // If it was approved but not applied, re-lock the entities
        if ($changeOrder->status === ChangeOrder::STATUS_APPROVED) {
            $this->lockService->relockAfterChangeOrder($changeOrder);
        }

        $changeOrder->update([
            'status' => ChangeOrder::STATUS_CANCELLED,
        ]);

        Log::info('Change order cancelled', [
            'change_order_id' => $changeOrder->id,
        ]);

        return $changeOrder->fresh();
    }

    /**
     * Calculate price and BOM totals for a change order.
     *
     * @param ChangeOrder $changeOrder
     * @return void
     */
    protected function calculateTotals(ChangeOrder $changeOrder): void
    {
        $lines = $changeOrder->lines;

        $priceDelta = $lines->sum('price_impact');
        
        $bomDelta = [
            'additions' => [],
            'removals' => [],
        ];

        foreach ($lines as $line) {
            if ($line->bom_impact_json) {
                $impact = $line->bom_impact_json;
                if (!empty($impact['additions'])) {
                    $bomDelta['additions'] = array_merge($bomDelta['additions'], $impact['additions']);
                }
                if (!empty($impact['removals'])) {
                    $bomDelta['removals'] = array_merge($bomDelta['removals'], $impact['removals']);
                }
            }
        }

        $changeOrder->update([
            'price_delta' => $priceDelta,
            'bom_delta_json' => $bomDelta,
        ]);
    }

    /**
     * Apply a single line item change.
     *
     * @param ChangeOrderLine $line
     * @return void
     */
    protected function applyLine(ChangeOrderLine $line): void
    {
        $entity = $this->getEntity($line->entity_type, $line->entity_id);

        if (!$entity) {
            Log::warning('Entity not found for change order line', [
                'line_id' => $line->id,
                'entity_type' => $line->entity_type,
                'entity_id' => $line->entity_id,
            ]);
            return;
        }

        // Bypass lock check for this save operation
        if (method_exists($entity, 'withoutLockCheck')) {
            $entity->withoutLockCheck(function ($model) use ($line) {
                $model->{$line->field_name} = $line->new_value;
                $model->save();
            });
        } else {
            $entity->{$line->field_name} = $line->new_value;
            $entity->save();
        }

        $line->update([
            'is_applied' => true,
            'applied_at' => now(),
        ]);
    }

    /**
     * Get an entity by type and ID.
     *
     * @param string $entityType
     * @param int $entityId
     * @return Model|null
     */
    protected function getEntity(string $entityType, int $entityId): ?Model
    {
        $modelClass = $this->resolveEntityClass($entityType);
        
        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($entityId);
    }

    /**
     * Resolve the model class from entity type.
     *
     * @param string $entityType
     * @return string|null
     */
    protected function resolveEntityClass(string $entityType): ?string
    {
        $namespace = 'Webkul\\Project\\Models\\';
        $fullClass = $namespace . $entityType;

        if (class_exists($fullClass)) {
            return $fullClass;
        }

        // Fallback mappings
        $typeMap = [
            'BomLine' => 'Webkul\\Project\\Models\\CabinetMaterialsBom',
        ];

        return $typeMap[$entityType] ?? null;
    }

    /**
     * Preview the impact of a change order.
     *
     * @param ChangeOrder $changeOrder
     * @return array
     */
    public function previewImpact(ChangeOrder $changeOrder): array
    {
        $lines = $changeOrder->lines;

        return [
            'lines_count' => $lines->count(),
            'price_delta' => $changeOrder->price_delta,
            'bom_additions' => count($changeOrder->bom_delta_json['additions'] ?? []),
            'bom_removals' => count($changeOrder->bom_delta_json['removals'] ?? []),
            'affected_entities' => $lines->groupBy('entity_type')->map->count()->toArray(),
            'field_changes' => $lines->map(function ($line) {
                return [
                    'entity' => "{$line->entity_type} #{$line->entity_id}",
                    'field' => $line->field_name,
                    'old' => $line->old_value,
                    'new' => $line->new_value,
                ];
            })->toArray(),
        ];
    }
}
