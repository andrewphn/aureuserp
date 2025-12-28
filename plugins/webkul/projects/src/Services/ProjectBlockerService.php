<?php

namespace Webkul\Project\Services;

use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;

class ProjectBlockerService
{
    /**
     * Get project blockers
     */
    public function getBlockers(Project $project): array
    {
        $blockers = [];

        // PRIMARY: Check for blocked tasks
        $blockedTasks = $project->tasks()->where('state', 'blocked')->count();
        if ($blockedTasks > 0) {
            $blockers[] = $blockedTasks . ' task(s) blocked';
        }

        // SECONDARY: Check for missing order (only if no blocked tasks)
        if (empty($blockers) && !$project->orders()->exists()) {
            $blockers[] = 'No sales order linked';
        }

        // SECONDARY: Check for missing customer
        if (empty($blockers) && !$project->partner_id) {
            $blockers[] = 'No customer assigned';
        }

        // Check for project dependencies
        if ($project->dependsOn->where('is_completed', false)->count() > 0) {
            $blockers[] = 'Waiting on dependencies';
        }

        return $blockers;
    }

    /**
     * Check if project is blocked
     */
    public function isBlocked(Project $project): bool
    {
        return !empty($this->getBlockers($project));
    }

    /**
     * Get project priority
     */
    public function getPriority(Project $project): ?string
    {
        // Check if overdue - high priority
        if ($project->desired_completion_date && $project->desired_completion_date < now()) {
            return 'high';
        }

        // Check complexity score
        $score = $project->complexity_score ?? 0;
        if ($score >= 8) {
            return 'high';
        }
        if ($score >= 5) {
            return 'medium';
        }

        // Check if due within 7 days
        if ($project->desired_completion_date && $project->desired_completion_date->diffInDays(now()) < 7) {
            return 'medium';
        }

        return null;
    }

    /**
     * Toggle project blocked status
     */
    public function toggleBlocked(Project $project): array
    {
        $blockedTask = $project->tasks()->where('state', 'blocked')->first();

        if ($blockedTask) {
            // Unblock all blocked tasks
            $count = $project->tasks()->where('state', 'blocked')->update(['state' => 'pending']);

            return [
                'action' => 'unblocked',
                'message' => 'All blocked tasks have been set to pending.',
                'count' => $count,
            ];
        }

        // Create a blocker
        $pendingTask = $project->tasks()->whereIn('state', ['pending', 'in_progress'])->first();

        if ($pendingTask) {
            $pendingTask->update(['state' => 'blocked']);

            return [
                'action' => 'blocked',
                'message' => "Task '{$pendingTask->title}' marked as blocked.",
                'task' => $pendingTask,
            ];
        }

        // Create a new blocked task
        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Blocker - Needs attention',
            'state' => 'blocked',
            'creator_id' => auth()->id(),
        ]);

        return [
            'action' => 'blocked',
            'message' => 'Created a blocker task.',
            'task' => $task,
        ];
    }

    /**
     * Get task progress info
     */
    public function getTaskProgress(Task $task): array
    {
        $totalSubtasks = $task->subTasks->count();
        $completedSubtasks = $task->subTasks->where('state', 'done')->count();

        if ($totalSubtasks > 0) {
            $progressPercent = round(($completedSubtasks / $totalSubtasks) * 100);
            $progressLabel = "$completedSubtasks/$totalSubtasks subtasks";
        } elseif ($task->allocated_hours > 0) {
            $progressPercent = min(100, round(($task->effective_hours / $task->allocated_hours) * 100));
            $progressLabel = number_format($task->effective_hours, 1) . '/' . number_format($task->allocated_hours, 1) . ' hrs';
        } else {
            $progressPercent = $task->progress ?? 0;
            $progressLabel = $progressPercent . '%';
        }

        return [
            'percent' => $progressPercent,
            'label' => $progressLabel,
        ];
    }

    /**
     * Check if task is overdue
     */
    public function isTaskOverdue(Task $task): bool
    {
        return $task->deadline && $task->deadline < now() && $task->state !== 'done';
    }

    /**
     * Check if task is due soon (within 7 days)
     */
    public function isTaskDueSoon(Task $task): bool
    {
        if (!$task->deadline || $task->state === 'done') {
            return false;
        }

        $daysLeft = now()->diffInDays($task->deadline, false);
        return $daysLeft >= 0 && $daysLeft <= 7;
    }
}
