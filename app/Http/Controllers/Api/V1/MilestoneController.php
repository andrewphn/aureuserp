<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Webkul\Project\Models\Milestone;

class MilestoneController extends BaseResourceController
{
    protected string $modelClass = Milestone::class;

    protected array $searchableFields = [
        'name',
        'description',
    ];

    protected array $filterableFields = [
        'id',
        'project_id',
        'is_reached',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'deadline',
        'sort',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'project',
        'tasks',
    ];

    protected function validateStore(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects_projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'is_reached' => 'nullable|boolean',
            'sort' => 'nullable|integer|min:0',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'is_reached' => 'nullable|boolean',
            'sort' => 'nullable|integer|min:0',
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
