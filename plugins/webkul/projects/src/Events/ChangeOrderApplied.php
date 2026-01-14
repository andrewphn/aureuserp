<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\ChangeOrder;

/**
 * Event fired when a change order is applied.
 *
 * At this point, the locked entities have been modified and re-locked.
 */
class ChangeOrderApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChangeOrder $changeOrder;

    public function __construct(ChangeOrder $changeOrder)
    {
        $this->changeOrder = $changeOrder;
    }

    public function getProject()
    {
        return $this->changeOrder->project;
    }

    public function getLinesApplied()
    {
        return $this->changeOrder->lines()->where('is_applied', true)->get();
    }

    public function getPriceDelta()
    {
        return $this->changeOrder->price_delta;
    }
}
