<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\RoomLocation;

class RoomLocationController extends BaseResourceController
{
    protected string $modelClass = RoomLocation::class;

    protected array $searchableFields = [
        'name',
        'location_code',
        'notes',
    ];

    protected array $filterableFields = [
        'id',
        'room_id',
        'cabinet_level',
        'material_category',
        'finish_option',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'location_code',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'room',
        'cabinetRuns',
    ];

    protected function getBaseQuery(): Builder
    {
        $query = parent::getBaseQuery();

        // If nested under room, scope to that room
        if ($roomId = request()->route('room')) {
            $query->where('room_id', $roomId);
        }

        return $query;
    }

    protected function validateStore(): array
    {
        // If nested under room route, room_id comes from URL
        $roomIdRule = request()->route('room')
            ? 'nullable|integer|exists:projects_rooms,id'
            : 'required|integer|exists:projects_rooms,id';

        return [
            'room_id' => $roomIdRule,
            'name' => 'required|string|max:255',
            'location_code' => 'nullable|string|max:50',
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
            'location_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if ($roomId = $request->route('room')) {
            $data['room_id'] = $roomId;
        }

        return $data;
    }
}
