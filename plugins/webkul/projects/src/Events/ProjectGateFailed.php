<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\Project;

/**
 * Event fired when a project fails to pass a gate.
 *
 * This event is useful for triggering notifications about blockers.
 */
class ProjectGateFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Project $project;
    public Gate $gate;
    public GateEvaluation $evaluation;
    public array $failureReasons;

    public function __construct(
        Project $project,
        Gate $gate,
        GateEvaluation $evaluation,
        array $failureReasons
    ) {
        $this->project = $project;
        $this->gate = $gate;
        $this->evaluation = $evaluation;
        $this->failureReasons = $failureReasons;
    }

    /**
     * Get a summary of blockers for display.
     */
    public function getBlockerSummary(): array
    {
        return array_map(function ($reason) {
            return [
                'message' => $reason['error_message'] ?? 'Unknown blocker',
                'help' => $reason['help_text'] ?? null,
                'action' => $reason['action_label'] ?? null,
            ];
        }, $this->failureReasons);
    }

    /**
     * Get count of blockers.
     */
    public function getBlockerCount(): int
    {
        return count($this->failureReasons);
    }
}
