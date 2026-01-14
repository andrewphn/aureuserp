<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\Project;

/**
 * Event fired when a project's design is locked.
 *
 * Design lock prevents editing of cabinet specs, sections, and components.
 */
class ProjectDesignLocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Project $project;
    public Gate $gate;
    public array $bomSnapshot;
    public array $pricingSnapshot;

    public function __construct(
        Project $project,
        Gate $gate,
        array $bomSnapshot = [],
        array $pricingSnapshot = []
    ) {
        $this->project = $project;
        $this->gate = $gate;
        $this->bomSnapshot = $bomSnapshot;
        $this->pricingSnapshot = $pricingSnapshot;
    }
}
