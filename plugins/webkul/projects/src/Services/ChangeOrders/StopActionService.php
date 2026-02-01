<?php

namespace Webkul\Project\Services\ChangeOrders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Models\ChangeOrderStopAction;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order as PurchaseOrder;

/**
 * Stop Action Service
 *
 * Manages stop actions when change orders are approved/applied/cancelled.
 *
 * When a change order is APPROVED:
 * - Block related production tasks
 * - Hold related purchase orders (if project in procurement stage)
 * - Flag project delivery as blocked
 *
 * When a change order is APPLIED or CANCELLED:
 * - Revert all stop actions
 * - Restore previous task/PO states
 * - Clear project flags
 */
class StopActionService
{
    /**
     * Execute stop actions when a change order is approved.
     *
     * @return array Summary of actions taken
     */
    public function executeStopActions(ChangeOrder $changeOrder): array
    {
        $project = $changeOrder->project;
        $summary = [
            'tasks_blocked' => 0,
            'pos_held' => 0,
            'delivery_blocked' => false,
        ];

        DB::transaction(function () use ($changeOrder, $project, &$summary) {
            // Block related tasks
            $summary['tasks_blocked'] = $this->blockTasks($changeOrder, $project);

            // Hold purchase orders if project is in procurement stage
            $summary['pos_held'] = $this->holdPurchaseOrders($changeOrder, $project);

            // Flag delivery as blocked
            $summary['delivery_blocked'] = $this->blockDelivery($changeOrder, $project);

            // Update project flags
            $project->update([
                'has_pending_change_order' => true,
                'active_change_order_id' => $changeOrder->id,
            ]);
        });

        Log::info('Stop actions executed for change order', [
            'change_order_id' => $changeOrder->id,
            'project_id' => $project->id,
            'tasks_blocked' => $summary['tasks_blocked'],
            'pos_held' => $summary['pos_held'],
            'delivery_blocked' => $summary['delivery_blocked'],
        ]);

        return $summary;
    }

    /**
     * Revert all stop actions when a change order is applied or cancelled.
     */
    public function revertStopActions(ChangeOrder $changeOrder): array
    {
        $project = $changeOrder->project;
        $summary = [
            'tasks_unblocked' => 0,
            'pos_released' => 0,
            'delivery_unblocked' => false,
        ];

        DB::transaction(function () use ($changeOrder, $project, &$summary) {
            // Unblock tasks
            $summary['tasks_unblocked'] = $this->unblockTasks($changeOrder);

            // Release held purchase orders
            $summary['pos_released'] = $this->releasePurchaseOrders($changeOrder);

            // Clear delivery blocked flag
            if ($project->delivery_blocked) {
                $project->delivery_blocked = false;
                $summary['delivery_unblocked'] = true;
            }

            // Clear project flags
            $project->update([
                'has_pending_change_order' => false,
                'active_change_order_id' => null,
                'delivery_blocked' => false,
            ]);
        });

        Log::info('Stop actions reverted for change order', [
            'change_order_id' => $changeOrder->id,
            'project_id' => $project->id,
            'tasks_unblocked' => $summary['tasks_unblocked'],
            'pos_released' => $summary['pos_released'],
            'delivery_unblocked' => $summary['delivery_unblocked'],
        ]);

        return $summary;
    }

    /**
     * Block all active tasks for the project.
     *
     * @return int Number of tasks blocked
     */
    protected function blockTasks(ChangeOrder $changeOrder, Project $project): int
    {
        $count = 0;
        $userId = auth()->id();

        // Get tasks that can be blocked (not already done/cancelled/blocked)
        $tasks = Task::where('project_id', $project->id)
            ->whereIn('state', [
                TaskState::PENDING,
                TaskState::IN_PROGRESS,
                TaskState::APPROVED,
            ])
            ->whereNull('blocked_by_change_order_id')
            ->get();

        foreach ($tasks as $task) {
            $previousState = $task->state->value;

            // Update task state to blocked
            $task->update([
                'blocked_by_change_order_id' => $changeOrder->id,
                'state_before_block' => $previousState,
                'state' => TaskState::BLOCKED,
            ]);

            // Record the stop action for audit
            ChangeOrderStopAction::create([
                'change_order_id' => $changeOrder->id,
                'action_type' => ChangeOrderStopAction::TYPE_TASK_BLOCKED,
                'entity_type' => 'Task',
                'entity_id' => $task->id,
                'previous_state' => $previousState,
                'new_state' => TaskState::BLOCKED->value,
                'performed_by' => $userId,
                'performed_at' => now(),
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Unblock tasks that were blocked by this change order.
     *
     * @return int Number of tasks unblocked
     */
    protected function unblockTasks(ChangeOrder $changeOrder): int
    {
        $count = 0;
        $userId = auth()->id();

        // Get stop actions for blocked tasks
        $stopActions = ChangeOrderStopAction::forChangeOrder($changeOrder->id)
            ->ofType(ChangeOrderStopAction::TYPE_TASK_BLOCKED)
            ->active()
            ->get();

        foreach ($stopActions as $stopAction) {
            $task = Task::find($stopAction->entity_id);

            if (!$task) {
                // Task was deleted, just mark action as reverted
                $stopAction->markReverted($userId);
                continue;
            }

            // Restore previous state
            $previousState = $stopAction->previous_state ?? TaskState::PENDING->value;

            $task->update([
                'blocked_by_change_order_id' => null,
                'state_before_block' => null,
                'state' => $previousState,
            ]);

            // Mark stop action as reverted
            $stopAction->markReverted($userId);

            $count++;
        }

        return $count;
    }

    /**
     * Hold purchase orders related to the project.
     *
     * Only holds POs that are in states where holding makes sense.
     *
     * @return int Number of POs held
     */
    protected function holdPurchaseOrders(ChangeOrder $changeOrder, Project $project): int
    {
        $count = 0;
        $userId = auth()->id();

        // Get purchase orders linked to this project's sales orders
        // that are in states that can be held
        $salesOrderIds = $project->orders()->pluck('id');

        if ($salesOrderIds->isEmpty()) {
            return 0;
        }

        // Find POs that originated from this project's sales orders
        // or have a reference to this project
        $purchaseOrders = PurchaseOrder::where(function ($query) use ($project, $salesOrderIds) {
            // POs with origin containing project name or sales order references
            $query->where('origin', 'like', "%{$project->name}%")
                ->orWhere('origin', 'like', "%{$project->project_number}%");
        })
            ->whereIn('state', [
                OrderState::DRAFT,
                OrderState::SENT,
                OrderState::PURCHASE,
            ])
            ->whereNull('held_by_change_order_id')
            ->get();

        foreach ($purchaseOrders as $po) {
            $previousState = $po->state->value;

            // Update PO state to on_hold
            $po->update([
                'held_by_change_order_id' => $changeOrder->id,
                'held_at' => now(),
                'held_by' => $userId,
                'state_before_hold' => $previousState,
                'state' => OrderState::ON_HOLD,
            ]);

            // Record the stop action for audit
            ChangeOrderStopAction::create([
                'change_order_id' => $changeOrder->id,
                'action_type' => ChangeOrderStopAction::TYPE_PO_HELD,
                'entity_type' => 'PurchaseOrder',
                'entity_id' => $po->id,
                'previous_state' => $previousState,
                'new_state' => OrderState::ON_HOLD->value,
                'performed_by' => $userId,
                'performed_at' => now(),
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Release purchase orders that were held by this change order.
     *
     * @return int Number of POs released
     */
    protected function releasePurchaseOrders(ChangeOrder $changeOrder): int
    {
        $count = 0;
        $userId = auth()->id();

        // Get stop actions for held POs
        $stopActions = ChangeOrderStopAction::forChangeOrder($changeOrder->id)
            ->ofType(ChangeOrderStopAction::TYPE_PO_HELD)
            ->active()
            ->get();

        foreach ($stopActions as $stopAction) {
            $po = PurchaseOrder::find($stopAction->entity_id);

            if (!$po) {
                // PO was deleted, just mark action as reverted
                $stopAction->markReverted($userId);
                continue;
            }

            // Restore previous state
            $previousState = $stopAction->previous_state ?? OrderState::DRAFT->value;

            $po->update([
                'held_by_change_order_id' => null,
                'held_at' => null,
                'held_by' => null,
                'state_before_hold' => null,
                'state' => $previousState,
            ]);

            // Mark stop action as reverted
            $stopAction->markReverted($userId);

            $count++;
        }

        return $count;
    }

    /**
     * Block delivery schedule for the project.
     *
     * @return bool Whether delivery was blocked
     */
    protected function blockDelivery(ChangeOrder $changeOrder, Project $project): bool
    {
        if ($project->delivery_blocked) {
            return false; // Already blocked
        }

        $userId = auth()->id();

        $project->update(['delivery_blocked' => true]);

        // Record the stop action
        ChangeOrderStopAction::create([
            'change_order_id' => $changeOrder->id,
            'action_type' => ChangeOrderStopAction::TYPE_DELIVERY_BLOCKED,
            'entity_type' => 'Project',
            'entity_id' => $project->id,
            'previous_state' => 'not_blocked',
            'new_state' => 'blocked',
            'performed_by' => $userId,
            'performed_at' => now(),
        ]);

        return true;
    }

    /**
     * Get summary of current stop actions for a change order.
     */
    public function getStopActionsSummary(ChangeOrder $changeOrder): array
    {
        return [
            'tasks_blocked' => ChangeOrderStopAction::forChangeOrder($changeOrder->id)
                ->ofType(ChangeOrderStopAction::TYPE_TASK_BLOCKED)
                ->active()
                ->count(),
            'pos_held' => ChangeOrderStopAction::forChangeOrder($changeOrder->id)
                ->ofType(ChangeOrderStopAction::TYPE_PO_HELD)
                ->active()
                ->count(),
            'delivery_blocked' => ChangeOrderStopAction::forChangeOrder($changeOrder->id)
                ->ofType(ChangeOrderStopAction::TYPE_DELIVERY_BLOCKED)
                ->active()
                ->exists(),
            'total_active' => ChangeOrderStopAction::forChangeOrder($changeOrder->id)
                ->active()
                ->count(),
        ];
    }
}
