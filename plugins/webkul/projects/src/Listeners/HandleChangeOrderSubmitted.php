<?php

namespace Webkul\Project\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Events\ChangeOrderSubmitted;
use Webkul\Project\Services\ChangeOrders\ChangeOrderNotificationService;

/**
 * Handles change order submission events.
 *
 * When a change order is submitted for approval:
 * - Marks the project as having a pending change order
 * - Notifies the PM that approval is needed
 */
class HandleChangeOrderSubmitted
{
    protected ChangeOrderNotificationService $notificationService;

    public function __construct(ChangeOrderNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ChangeOrderSubmitted $event): void
    {
        $changeOrder = $event->changeOrder;
        $project = $event->getProject();

        Log::info('Change order submitted for approval', [
            'change_order_id' => $changeOrder->id,
            'change_order_number' => $changeOrder->change_order_number,
            'project_id' => $project->id,
            'requested_by' => $changeOrder->requested_by,
        ]);

        // Mark project as having a pending change order
        // Note: At submission, we only flag the project - stop actions happen at approval
        $project->update([
            'has_pending_change_order' => true,
        ]);

        // Notify PM
        $this->notificationService->notifySubmitted($changeOrder);
    }
}
