<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if there are no blocking defects/issues open for the project.
 */
class NoBlockingDefectsCheck
{
    /**
     * Check if no blocking defects exist.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        // Check for blocking issues in tasks (tasks marked as blocking/defects)
        $blockingTasks = $project->tasks()
            ->where(function ($query) {
                $query->where('is_blocking', true)
                    ->orWhere('task_type', 'defect')
                    ->orWhere('task_type', 'remediation');
            })
            ->where('state', '!=', 'done')
            ->where('state', '!=', 'cancelled')
            ->get();

        // Also check QC failures on cabinets
        $qcFailedCabinets = $project->cabinets()
            ->where('qc_passed', false)
            ->whereNotNull('qc_notes') // Has QC notes indicating a review happened
            ->get();

        $blockingCount = $blockingTasks->count() + $qcFailedCabinets->count();
        $passed = $blockingCount === 0;

        $blockingDetails = [];
        
        foreach ($blockingTasks as $task) {
            $blockingDetails[] = [
                'type' => 'task',
                'id' => $task->id,
                'title' => $task->title,
                'task_type' => $task->task_type,
            ];
        }

        foreach ($qcFailedCabinets as $cabinet) {
            $blockingDetails[] = [
                'type' => 'qc_failure',
                'id' => $cabinet->id,
                'name' => $cabinet->name ?? "Cabinet #{$cabinet->id}",
                'notes' => $cabinet->qc_notes,
            ];
        }

        return new RequirementCheckResult(
            $passed,
            $passed 
                ? 'No blocking defects found'
                : "{$blockingCount} blocking issue(s) require resolution",
            [
                'blocking_tasks' => $blockingTasks->count(),
                'qc_failures' => $qcFailedCabinets->count(),
                'details' => $blockingDetails,
            ]
        );
    }
}
