<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\CabinetRun;

class CabinetRunController extends BaseResourceController
{
    protected string $modelClass = CabinetRun::class;

    protected array $searchableFields = [
        'name',
        'run_code',
        'notes',
    ];

    protected array $filterableFields = [
        'id',
        'room_location_id',
        'cabinet_level',
        'material_category',
        'finish_option',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'run_code',
        'sort_order',
        'total_length_inches',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'roomLocation',
        'cabinets',
    ];

    protected function getBaseQuery(): Builder
    {
        $query = parent::getBaseQuery();

        // If nested under location, scope to that location
        if ($locationId = request()->route('location')) {
            $query->where('room_location_id', $locationId);
        }

        return $query;
    }

    protected function validateStore(): array
    {
        return [
            'room_location_id' => 'required|integer|exists:projects_room_locations,id',
            'name' => 'nullable|string|max:255',
            'run_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'total_length_inches' => 'nullable|numeric|min:0',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'run_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'total_length_inches' => 'nullable|numeric|min:0',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if ($locationId = $request->route('location')) {
            $data['room_location_id'] = $locationId;
        }

        return $data;
    }
}
