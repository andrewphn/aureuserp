<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\Room;

class RoomController extends BaseResourceController
{
    protected string $modelClass = Room::class;

    protected array $searchableFields = [
        'name',
        'room_code',
        'notes',
    ];

    protected array $filterableFields = [
        'id',
        'project_id',
        'room_type',
        'floor_number',
        'cabinet_level',
        'material_category',
        'finish_option',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'room_code',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'project',
        'locations',
        'cabinets',
        'creator',
    ];

    protected function getBaseQuery(): Builder
    {
        $query = parent::getBaseQuery();

        // If nested under project, scope to that project
        if ($projectId = request()->route('project')) {
            $query->where('project_id', $projectId);
        }

        return $query;
    }

    protected function validateStore(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects_projects,id',
            'name' => 'required|string|max:255',
            'room_code' => 'nullable|string|max:50',
            'room_type' => 'nullable|string|max:50',
            'floor_number' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'room_code' => 'nullable|string|max:50',
            'room_type' => 'nullable|string|max:50',
            'floor_number' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        // If created under a project route, use that project_id
        if ($projectId = $request->route('project')) {
            $data['project_id'] = $projectId;
        }

        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        return $data;
    }
}
