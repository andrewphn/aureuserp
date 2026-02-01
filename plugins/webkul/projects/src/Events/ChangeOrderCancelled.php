<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\ChangeOrder;

/**
 * Event fired when a change order is cancelled.
 *
 * This event triggers:
 * - Reverting stop actions (if change order was approved)
 * - Clearing pending change order flags
 * - Notifying stakeholders
 */
class ChangeOrderCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChangeOrder $changeOrder;

    /**
     * Whether the change order was approved before cancellation.
     * If true, stop actions need to be reverted.
     */
    public bool $wasApproved;

    public function __construct(ChangeOrder $changeOrder, bool $wasApproved = false)
    {
        $this->changeOrder = $changeOrder;
        $this->wasApproved = $wasApproved;
    }

    public function getProject()
    {
        return $this->changeOrder->project;
    }

    /**
     * Check if stop actions need to be reverted.
     */
    public function needsStopActionReversal(): bool
    {
        return $this->wasApproved;
    }
}
