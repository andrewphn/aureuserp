<?php

namespace Webkul\Project\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\GateEvaluationResult;

/**
 * Event fired when a gate is evaluated.
 *
 * This event fires regardless of whether the gate passed or failed.
 * Use ProjectGatePassed or ProjectGateFailed for specific outcomes.
 */
class ProjectGateEvaluated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Project $project;
    public Gate $gate;
    public GateEvaluation $evaluation;
    public bool $passed;
    public array $failureReasons;

    public function __construct(
        Project $project,
        Gate $gate,
        GateEvaluation $evaluation,
        bool $passed,
        array $failureReasons = []
    ) {
        $this->project = $project;
        $this->gate = $gate;
        $this->evaluation = $evaluation;
        $this->passed = $passed;
        $this->failureReasons = $failureReasons;
    }

    /**
     * Create from a GateEvaluationResult.
     */
    public static function fromResult(Project $project, GateEvaluationResult $result): self
    {
        return new self(
            $project,
            $result->gate,
            $result->evaluation,
            $result->passed,
            $result->failureReasons
        );
    }
}
