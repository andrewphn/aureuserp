<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if all production-related tasks are completed.
 */
class AllProductionTasksCompleteCheck
{
    /**
     * Task types considered "production" tasks.
     */
    protected array $productionTaskTypes = [
        'cnc_cutting',
        'assembly',
        'finishing',
        'hardware_install',
        'production',
    ];

    /**
     * Check if all production tasks are complete.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        // Get all production-related tasks for the project
        $productionTasks = $project->tasks()
            ->where(function ($query) {
                $query->whereIn('task_type', $this->productionTaskTypes)
                    ->orWhere('requires_production', true);
            })
            ->get();

        if ($productionTasks->isEmpty()) {
            return new RequirementCheckResult(
                false,
                'No production tasks found for project',
                ['task_count' => 0]
            );
        }

        $total = $productionTasks->count();
        $completed = $productionTasks->filter(fn($task) => $task->state === 'done');
        $incomplete = $productionTasks->filter(fn($task) => $task->state !== 'done');

        $passed = $incomplete->isEmpty();

        $incompleteDetails = $incomplete->map(function ($task) {
            return [
                'task_id' => $task->id,
                'title' => $task->title,
                'state' => $task->state,
                'task_type' => $task->task_type,
            ];
        })->values()->toArray();

        return new RequirementCheckResult(
            $passed,
            $passed 
                ? "All {$total} production tasks completed"
                : "{$completed->count()}/{$total} production tasks completed - " . $incomplete->count() . " remaining",
            [
                'total' => $total,
                'completed' => $completed->count(),
                'incomplete' => $incompleteDetails,
            ]
        );
    }
}
