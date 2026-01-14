<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\Project;

/**
 * Event fired when a project's procurement is locked.
 *
 * Procurement lock prevents changes to BOM quantities and material selections.
 */
class ProjectProcurementLocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Project $project;
    public Gate $gate;

    public function __construct(Project $project, Gate $gate)
    {
        $this->project = $project;
        $this->gate = $gate;
    }
}
