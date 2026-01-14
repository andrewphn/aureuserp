<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\Project;

/**
 * Event fired when a project successfully passes a gate.
 *
 * This event is useful for triggering automations like:
 * - Creating follow-up tasks
 * - Sending notifications
 * - Applying locks
 * - Generating documents
 */
class ProjectGatePassed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Project $project;
    public Gate $gate;
    public GateEvaluation $evaluation;

    public function __construct(
        Project $project,
        Gate $gate,
        GateEvaluation $evaluation
    ) {
        $this->project = $project;
        $this->gate = $gate;
        $this->evaluation = $evaluation;
    }

    /**
     * Check if this gate applies design lock.
     */
    public function appliesDesignLock(): bool
    {
        return $this->gate->applies_design_lock;
    }

    /**
     * Check if this gate applies procurement lock.
     */
    public function appliesProcurementLock(): bool
    {
        return $this->gate->applies_procurement_lock;
    }

    /**
     * Check if this gate applies production lock.
     */
    public function appliesProductionLock(): bool
    {
        return $this->gate->applies_production_lock;
    }

    /**
     * Check if this gate creates tasks on pass.
     */
    public function createsTasks(): bool
    {
        return $this->gate->creates_tasks_on_pass;
    }

    /**
     * Get task templates to create.
     */
    public function getTaskTemplates(): array
    {
        return $this->gate->task_templates_json ?? [];
    }
}
