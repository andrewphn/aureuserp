<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\CabinetSection;

class CabinetSectionController extends BaseResourceController
{
    protected string $modelClass = CabinetSection::class;

    protected array $searchableFields = ['section_code', 'notes'];

    protected array $filterableFields = [
        'id',
        'cabinet_id',
        'section_type',
        'position_from_left',
    ];

    protected array $sortableFields = [
        'id',
        'section_code',
        'position_from_left',
        'width_inches',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'cabinet',
        'drawers',
        'doors',
        'shelves',
        'pullouts',
    ];

    protected function getBaseQuery(): Builder
    {
        $query = parent::getBaseQuery();

        if ($cabinetId = request()->route('cabinet')) {
            $query->where('cabinet_id', $cabinetId);
        }

        return $query;
    }

    protected function validateStore(): array
    {
        return [
            'cabinet_id' => 'required|integer|exists:projects_cabinets,id',
            'section_code' => 'nullable|string|max:50',
            'section_type' => 'nullable|string|max:50',
            'position_from_left' => 'nullable|integer|min:0',
            'width_inches' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'section_code' => 'sometimes|string|max:50',
            'section_type' => 'nullable|string|max:50',
            'position_from_left' => 'nullable|integer|min:0',
            'width_inches' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if ($cabinetId = $request->route('cabinet')) {
            $data['cabinet_id'] = $cabinetId;
        }

        return $data;
    }
}
