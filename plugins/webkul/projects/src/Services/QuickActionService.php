<?php

namespace Webkul\Project\Services;

use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Security\Models\User;

class QuickActionService
{
    /**
     * Toggle milestone completion status
     */
    public function toggleMilestone(int $milestoneId): ?Milestone
    {
        $milestone = Milestone::find($milestoneId);

        if (!$milestone) {
            return null;
        }

        $milestone->update([
            'is_completed' => !$milestone->is_completed,
            'completed_at' => !$milestone->is_completed ? now() : null,
        ]);

        return $milestone;
    }

    /**
     * Add a milestone to project
     */
    public function addMilestone(Project $project, string $title): Milestone
    {
        $maxSort = $project->milestones()->max('sort') ?? 0;

        return Milestone::create([
            'project_id' => $project->id,
            'name' => trim($title),
            'sort' => $maxSort + 1,
            'is_completed' => false,
        ]);
    }

    /**
     * Add a task to project
     */
    public function addTask(Project $project, string $title): Task
    {
        $stage = TaskStage::query()
            ->where(function ($q) use ($project) {
                $q->where('project_id', $project->id)
                    ->orWhereNull('project_id');
            })
            ->orderBy('sort')
            ->first();

        return Task::create([
            'project_id' => $project->id,
            'title' => trim($title),
            'state' => 'pending',
            'stage_id' => $stage?->id,
            'creator_id' => auth()->id(),
        ]);
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(int $taskId, string $state): ?Task
    {
        $task = Task::find($taskId);

        if (!$task) {
            return null;
        }

        $task->update([
            'state' => $state,
            'completed_at' => $state === 'done' ? now() : null,
        ]);

        return $task;
    }

    /**
     * Assign team member to project
     */
    public function assignTeamMember(Project $project, string $role, ?int $userId): bool
    {
        $fieldMap = [
            'pm' => 'user_id',
            'designer' => 'designer_id',
            'purchasing' => 'purchasing_manager_id',
        ];

        $field = $fieldMap[$role] ?? null;

        if (!$field) {
            return false;
        }

        return $project->update([$field => $userId]);
    }

    /**
     * Change project stage
     */
    public function changeProjectStage(Project $project, int $stageId): ?ProjectStage
    {
        $stage = ProjectStage::find($stageId);

        if (!$stage) {
            return null;
        }

        $project->update(['stage_id' => $stageId]);

        return $stage;
    }

    /**
     * Post a comment to project via Chatter
     */
    public function postComment(Project $project, string $body): bool
    {
        $trimmedBody = trim($body);

        if (empty($trimmedBody)) {
            return false;
        }

        if (method_exists($project, 'addMessage')) {
            $project->addMessage([
                'body' => $trimmedBody,
                'type' => 'comment',
            ]);
            return true;
        }

        // Fallback: Create activity directly
        $project->activities()->create([
            'type' => 'comment',
            'body' => $trimmedBody,
            'causer_type' => User::class,
            'causer_id' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Duplicate a project
     */
    public function duplicateProject(int $projectId): ?Project
    {
        $project = Project::find($projectId);

        if (!$project) {
            return null;
        }

        $newProject = $project->replicate();
        $newProject->name = $project->name . ' (Copy)';
        $newProject->save();

        return $newProject;
    }

    /**
     * Get available users for team assignment
     */
    public function getAvailableUsers(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get available project stages
     */
    public function getAvailableStages(): \Illuminate\Support\Collection
    {
        return ProjectStage::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get(['id', 'name', 'color']);
    }

    /**
     * Get role display name
     */
    public function getRoleName(string $role): string
    {
        return match ($role) {
            'pm' => 'Project Manager',
            'designer' => 'Designer',
            'purchasing' => 'Purchasing Manager',
            default => 'Team member',
        };
    }
}
