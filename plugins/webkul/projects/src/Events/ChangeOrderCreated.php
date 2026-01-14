<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\ChangeOrder;

/**
 * Event fired when a change order is created.
 */
class ChangeOrderCreated
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
}
