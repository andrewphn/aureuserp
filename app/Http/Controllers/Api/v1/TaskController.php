<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Webkul\Project\Models\Task;

class TaskController extends BaseResourceController
{
    protected string $modelClass = Task::class;

    protected array $searchableFields = [
        'name',
        'description',
    ];

    protected array $filterableFields = [
        'id',
        'project_id',
        'milestone_id',
        'stage_id',
        'user_id',
        'priority',
        'is_active',
        'parent_id',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'priority',
        'deadline',
        'sort',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'project',
        'milestone',
        'stage',
        'user',
        'creator',
        'parent',
        'children',
    ];

    protected function validateStore(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects_projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'milestone_id' => 'nullable|integer|exists:projects_milestones,id',
            'stage_id' => 'nullable|integer|exists:projects_task_stages,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'priority' => 'nullable|integer|min:0|max:10',
            'deadline' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|integer|exists:projects_tasks,id',
            'allocated_hours' => 'nullable|numeric|min:0',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'milestone_id' => 'nullable|integer|exists:projects_milestones,id',
            'stage_id' => 'nullable|integer|exists:projects_task_stages,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'priority' => 'nullable|integer|min:0|max:10',
            'deadline' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|integer|exists:projects_tasks,id',
            'allocated_hours' => 'nullable|numeric|min:0',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        return $data;
    }
}
