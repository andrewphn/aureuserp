<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\Cabinet;

class CabinetController extends BaseResourceController
{
    protected string $modelClass = Cabinet::class;

    protected array $searchableFields = [
        'cabinet_number',
        'full_code',
        'shop_notes',
        'hardware_notes',
    ];

    protected array $filterableFields = [
        'id',
        'project_id',
        'room_id',
        'cabinet_run_id',
        'cabinet_level',
        'material_category',
        'finish_option',
        'door_style',
        'door_mounting',
        'construction_type',
    ];

    protected array $sortableFields = [
        'id',
        'cabinet_number',
        'position_in_run',
        'length_inches',
        'width_inches',
        'depth_inches',
        'height_inches',
        'linear_feet',
        'total_price',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'project',
        'room',
        'cabinetRun',
        'sections',
        'stretchers',
        'creator',
    ];

    protected function getBaseQuery(): Builder
    {
        $query = parent::getBaseQuery();

        // If nested under cabinet_run, scope to that run
        if ($cabinetRunId = request()->route('cabinet_run')) {
            $query->where('cabinet_run_id', $cabinetRunId);
        }

        return $query;
    }

    protected function validateStore(): array
    {
        return [
            'cabinet_run_id' => 'required|integer|exists:projects_cabinet_runs,id',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'room_id' => 'nullable|integer|exists:projects_rooms,id',
            'cabinet_number' => 'nullable|string|max:50',
            'position_in_run' => 'nullable|integer|min:0',
            'wall_position_start_inches' => 'nullable|numeric|min:0',
            'length_inches' => 'required|numeric|min:1',
            'width_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'required|numeric|min:1',
            'height_inches' => 'required|numeric|min:1',
            'quantity' => 'nullable|integer|min:1',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
            'door_style' => 'nullable|string|max:100',
            'door_mounting' => 'nullable|string|max:50',
            'door_count' => 'nullable|integer|min:0',
            'drawer_count' => 'nullable|integer|min:0',
            'shop_notes' => 'nullable|string',
            'hardware_notes' => 'nullable|string',
            'custom_modifications' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'cabinet_number' => 'sometimes|string|max:50',
            'position_in_run' => 'nullable|integer|min:0',
            'wall_position_start_inches' => 'nullable|numeric|min:0',
            'length_inches' => 'sometimes|numeric|min:1',
            'width_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'sometimes|numeric|min:1',
            'height_inches' => 'sometimes|numeric|min:1',
            'quantity' => 'nullable|integer|min:1',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
            'door_style' => 'nullable|string|max:100',
            'door_mounting' => 'nullable|string|max:50',
            'door_count' => 'nullable|integer|min:0',
            'drawer_count' => 'nullable|integer|min:0',
            'shop_notes' => 'nullable|string',
            'hardware_notes' => 'nullable|string',
            'custom_modifications' => 'nullable|string',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if ($cabinetRunId = $request->route('cabinet_run')) {
            $data['cabinet_run_id'] = $cabinetRunId;

            // Auto-populate project_id and room_id from cabinet run
            $cabinetRun = \Webkul\Project\Models\CabinetRun::with('roomLocation.room')->find($cabinetRunId);
            if ($cabinetRun && $cabinetRun->roomLocation) {
                $data['room_id'] = $cabinetRun->roomLocation->room_id;
                $data['project_id'] = $cabinetRun->roomLocation->room->project_id ?? null;
            }
        }

        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        if (!isset($data['quantity'])) {
            $data['quantity'] = 1;
        }

        return $data;
    }
}
