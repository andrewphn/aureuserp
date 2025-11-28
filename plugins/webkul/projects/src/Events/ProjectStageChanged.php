<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;

/**
 * Event fired when a project's stage changes.
 *
 * This event enables inventory and other integrations to react
 * to project workflow transitions.
 */
class ProjectStageChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The project that changed stages.
     */
    public Project $project;

    /**
     * The previous stage (null if first assignment).
     */
    public ?ProjectStage $previousStage;

    /**
     * The new stage.
     */
    public ProjectStage $newStage;

    /**
     * The user who triggered the change.
     */
    public ?int $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Project $project,
        ?ProjectStage $previousStage,
        ProjectStage $newStage,
        ?int $userId = null
    ) {
        $this->project = $project;
        $this->previousStage = $previousStage;
        $this->newStage = $newStage;
        $this->userId = $userId ?? auth()->id();
    }

    /**
     * Get the previous stage key.
     */
    public function getPreviousStageKey(): ?string
    {
        return $this->previousStage?->stage_key;
    }

    /**
     * Get the new stage key.
     */
    public function getNewStageKey(): ?string
    {
        return $this->newStage->stage_key;
    }

    /**
     * Check if transitioning to a specific stage.
     */
    public function isTransitioningTo(string $stageKey): bool
    {
        return $this->newStage->stage_key === $stageKey;
    }

    /**
     * Check if transitioning from a specific stage.
     */
    public function isTransitioningFrom(string $stageKey): bool
    {
        return $this->previousStage?->stage_key === $stageKey;
    }
}
