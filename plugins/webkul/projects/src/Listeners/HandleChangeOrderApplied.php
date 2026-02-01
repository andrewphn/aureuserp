<?php

namespace Webkul\Project\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Events\ChangeOrderApplied;
use Webkul\Project\Services\ChangeOrders\ChangeOrderNotificationService;
use Webkul\Project\Services\ChangeOrders\StopActionService;

/**
 * Handles change order application events.
 *
 * When a change order is applied:
 * - Reverts all stop actions (unblock tasks, release POs)
 * - Clears project pending change order flags
 * - Notifies stakeholders that work can resume
 */
class HandleChangeOrderApplied
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
    public function handle(ChangeOrderApplied $event): void
    {
        $changeOrder = $event->changeOrder;
        $project = $event->getProject();

        Log::info('Change order applied - reverting stop actions', [
            'change_order_id' => $changeOrder->id,
            'change_order_number' => $changeOrder->change_order_number,
            'project_id' => $project->id,
            'applied_by' => $changeOrder->applied_by,
        ]);

        // Revert stop actions
        $summary = $this->stopActionService->revertStopActions($changeOrder);

        Log::info('Stop actions reverted', [
            'change_order_id' => $changeOrder->id,
            'tasks_unblocked' => $summary['tasks_unblocked'],
            'pos_released' => $summary['pos_released'],
            'delivery_unblocked' => $summary['delivery_unblocked'],
        ]);

        // Notify stakeholders that work can resume
        $this->notificationService->notifyApplied($changeOrder);
    }
}
