<?php

namespace Webkul\Project\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Events\ChangeOrderApproved;
use Webkul\Project\Services\ChangeOrders\ChangeOrderNotificationService;
use Webkul\Project\Services\ChangeOrders\StopActionService;

/**
 * Handles change order approval events.
 *
 * When a change order is approved:
 * - Executes stop actions (block tasks, hold POs, block delivery)
 * - Notifies all stakeholders
 */
class HandleChangeOrderApproved
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
    public function handle(ChangeOrderApproved $event): void
    {
        $changeOrder = $event->changeOrder;
        $project = $event->getProject();

        Log::info('Change order approved - executing stop actions', [
            'change_order_id' => $changeOrder->id,
            'change_order_number' => $changeOrder->change_order_number,
            'project_id' => $project->id,
            'approved_by' => $changeOrder->approved_by,
        ]);

        // Execute stop actions
        $summary = $this->stopActionService->executeStopActions($changeOrder);

        // Notify stakeholders
        $this->notificationService->notifyApproved($changeOrder, $summary);
    }
}
