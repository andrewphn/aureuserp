<?php

namespace App\Http\Controllers\Api\V1;

use Webkul\Project\Models\Project;

class ProjectController extends BaseResourceController
{
    protected string $modelClass = Project::class;

    protected array $searchableFields = [
        'name',
        'project_number',
        'draft_number',
        'description',
    ];

    protected array $filterableFields = [
        'id',
        'is_active',
        'is_converted',
        'stage_id',
        'partner_id',
        'company_id',
        'user_id',
        'creator_id',
        'project_type',
        'visibility',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'project_number',
        'created_at',
        'updated_at',
        'start_date',
        'end_date',
        'estimated_linear_feet',
    ];

    protected array $includableRelations = [
        'rooms',
        'cabinets',
        'partner',
        'creator',
        'user',
        'stage',
        'company',
        'tags',
        'milestones',
        'tasks',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'partner_id' => 'nullable|integer|exists:partners_partners,id',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'stage_id' => 'nullable|integer|exists:projects_project_stages,id',
            'project_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'desired_completion_date' => 'nullable|date',
            'allocated_hours' => 'nullable|numeric|min:0',
            'estimated_linear_feet' => 'nullable|string|max:50',
            'visibility' => 'nullable|string|in:public,private',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'partner_id' => 'nullable|integer|exists:partners_partners,id',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'stage_id' => 'nullable|integer|exists:projects_project_stages,id',
            'project_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'desired_completion_date' => 'nullable|date',
            'allocated_hours' => 'nullable|numeric|min:0',
            'estimated_linear_feet' => 'nullable|string|max:50',
            'visibility' => 'nullable|string|in:public,private',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function beforeStore(array $data, $request): array
    {
        // Set creator to current user if not provided
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        return $data;
    }
}
