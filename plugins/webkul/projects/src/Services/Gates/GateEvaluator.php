<?php

namespace Webkul\Project\Services\Gates;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;

/**
 * Gate Evaluator Service
 *
 * Evaluates gate requirements to determine if a project can advance to the next stage.
 * Creates audit records for all evaluations.
 */
class GateEvaluator
{
    protected GateRequirementChecker $requirementChecker;

    public function __construct(GateRequirementChecker $requirementChecker)
    {
        $this->requirementChecker = $requirementChecker;
    }

    /**
     * Evaluate a gate for a project.
     *
     * @param Project $project
     * @param Gate $gate
     * @param string $evaluationType
     * @return GateEvaluationResult
     */
    public function evaluate(Project $project, Gate $gate, string $evaluationType = GateEvaluation::TYPE_MANUAL): GateEvaluationResult
    {
        $requirements = $gate->requirements;
        $results = [];
        $failureReasons = [];
        $allPassed = true;

        foreach ($requirements as $requirement) {
            $checkResult = $this->requirementChecker->check($project, $requirement);
            
            $results[$requirement->id] = [
                'requirement_id' => $requirement->id,
                'requirement_type' => $requirement->requirement_type,
                'passed' => $checkResult->passed,
                'message' => $checkResult->message,
                'details' => $checkResult->details,
            ];

            if (!$checkResult->passed) {
                $allPassed = false;
                $failureReasons[] = [
                    'requirement_id' => $requirement->id,
                    'error_message' => $requirement->error_message,
                    'help_text' => $requirement->help_text,
                    'action_label' => $requirement->action_label,
                    'action_route' => $requirement->action_route,
                    'details' => $checkResult->message,
                ];
            }
        }

        // Build context snapshot
        $context = $this->buildContextSnapshot($project);

        // Record the evaluation
        $evaluation = GateEvaluation::record(
            $project,
            $gate,
            $allPassed,
            $results,
            $failureReasons,
            $context,
            $evaluationType
        );

        Log::info('Gate evaluated', [
            'project_id' => $project->id,
            'gate_key' => $gate->gate_key,
            'passed' => $allPassed,
            'failed_count' => count($failureReasons),
        ]);

        return new GateEvaluationResult(
            passed: $allPassed,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: $results,
            failureReasons: $failureReasons
        );
    }

    /**
     * Evaluate all gates for a project's current stage.
     *
     * @param Project $project
     * @return array<GateEvaluationResult>
     */
    public function evaluateCurrentStageGates(Project $project): array
    {
        $gates = $project->getCurrentStageGates();
        $results = [];

        foreach ($gates as $gate) {
            $results[$gate->gate_key] = $this->evaluate($project, $gate);
        }

        return $results;
    }

    /**
     * Check if a project can advance past all current stage gates.
     *
     * @param Project $project
     * @return bool
     */
    public function canAdvance(Project $project): bool
    {
        $gates = $project->getCurrentStageGates()->where('is_blocking', true);

        foreach ($gates as $gate) {
            $result = $this->evaluate($project, $gate, GateEvaluation::TYPE_AUTOMATIC);
            if (!$result->passed) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all blockers for the current stage.
     *
     * @param Project $project
     * @return array
     */
    public function getBlockers(Project $project): array
    {
        $allBlockers = [];
        $gates = $project->getCurrentStageGates()->where('is_blocking', true);

        foreach ($gates as $gate) {
            $result = $this->evaluate($project, $gate, GateEvaluation::TYPE_AUTOMATIC);
            if (!$result->passed) {
                $allBlockers[$gate->gate_key] = [
                    'gate' => $gate,
                    'blockers' => $result->failureReasons,
                ];
            }
        }

        return $allBlockers;
    }

    /**
     * Get a summary of gate status for the project.
     *
     * @param Project $project
     * @return array
     */
    public function getGateStatus(Project $project): array
    {
        $gates = $project->getCurrentStageGates();
        $status = [];

        foreach ($gates as $gate) {
            $result = $this->evaluate($project, $gate, GateEvaluation::TYPE_AUTOMATIC);
            
            $status[] = [
                'gate_key' => $gate->gate_key,
                'name' => $gate->name,
                'passed' => $result->passed,
                'is_blocking' => $gate->is_blocking,
                'requirements_total' => count($result->requirementResults),
                'requirements_passed' => collect($result->requirementResults)->where('passed', true)->count(),
                'blockers' => $result->failureReasons,
            ];
        }

        return $status;
    }

    /**
     * Build a context snapshot for the evaluation.
     *
     * @param Project $project
     * @return array
     */
    protected function buildContextSnapshot(Project $project): array
    {
        return [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
            'stage_id' => $project->stage_id,
            'stage_key' => $project->stage?->stage_key,
            'partner_id' => $project->partner_id,
            'room_count' => $project->rooms()->count(),
            'cabinet_count' => $project->cabinets()->count(),
            'has_sales_order' => $project->orders()->exists(),
            'design_approved_at' => $project->design_approved_at?->toIso8601String(),
            'redline_approved_at' => $project->redline_approved_at?->toIso8601String(),
            'all_materials_received_at' => $project->all_materials_received_at?->toIso8601String(),
            'materials_staged_at' => $project->materials_staged_at?->toIso8601String(),
            'delivered_at' => $project->delivered_at?->toIso8601String(),
            'snapshot_at' => now()->toIso8601String(),
        ];
    }
}
