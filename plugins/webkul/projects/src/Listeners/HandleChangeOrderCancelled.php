<?php

namespace Webkul\Project\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Events\ChangeOrderCancelled;
use Webkul\Project\Services\ChangeOrders\ChangeOrderNotificationService;
use Webkul\Project\Services\ChangeOrders\StopActionService;

/**
 * Handles change order cancellation events.
 *
 * When a change order is cancelled:
 * - If it was approved, reverts all stop actions
 * - Clears project pending change order flags
 * - Notifies stakeholders
 */
class HandleChangeOrderCancelled
{
    protected StopActionService $stopActionService;
    protected ChangeOrderNotificationService $notificationService;

    public function __construct(
        StopActionService $stopActionService,
        ChangeOrderNotificationService $notificationService
    ) {
        $this->stopActionService = $stopActionService;
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ChangeOrderCancelled $event): void
    {
        $changeOrder = $event->changeOrder;
        $project = $event->getProject();

        Log::info('Change order cancelled', [
            'change_order_id' => $changeOrder->id,
            'change_order_number' => $changeOrder->change_order_number,
            'project_id' => $project->id,
            'was_approved' => $event->wasApproved,
        ]);

        // If change order was approved, revert stop actions
        if ($event->needsStopActionReversal()) {
            $summary = $this->stopActionService->revertStopActions($changeOrder);

            Log::info('Stop actions reverted due to cancellation', [
                'change_order_id' => $changeOrder->id,
                'tasks_unblocked' => $summary['tasks_unblocked'],
                'pos_released' => $summary['pos_released'],
                'delivery_unblocked' => $summary['delivery_unblocked'],
            ]);
        } else {
            // Just clear project flags if it was only submitted (not approved)
            $project->update([
                'has_pending_change_order' => false,
                'active_change_order_id' => null,
            ]);
        }

        // Notify stakeholders
        $this->notificationService->notifyCancelled($changeOrder, $event->wasApproved);
    }
}
