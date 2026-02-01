<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\Shelf;

class ShelfController extends BaseResourceController
{
    protected string $modelClass = Shelf::class;

    protected array $searchableFields = ['shelf_code', 'notes'];

    protected array $filterableFields = [
        'id',
        'cabinet_section_id',
        'shelf_type',
        'is_adjustable',
    ];

    protected array $sortableFields = [
        'id',
        'shelf_code',
        'position_from_top',
        'depth_inches',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = ['cabinetSection'];

    protected function getBaseQuery(): Builder
    {
        $query = parent::getBaseQuery();

        if ($sectionId = request()->route('section')) {
            $query->where('cabinet_section_id', $sectionId);
        }

        return $query;
    }

    protected function validateStore(): array
    {
        return [
            'cabinet_section_id' => 'required|integer|exists:projects_cabinet_sections,id',
            'shelf_code' => 'nullable|string|max:50',
            'shelf_type' => 'nullable|string|max:50',
            'position_from_top' => 'nullable|integer|min:0',
            'depth_inches' => 'nullable|numeric|min:0',
            'is_adjustable' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'shelf_code' => 'sometimes|string|max:50',
            'shelf_type' => 'nullable|string|max:50',
            'position_from_top' => 'nullable|integer|min:0',
            'depth_inches' => 'nullable|numeric|min:0',
            'is_adjustable' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if ($sectionId = $request->route('section')) {
            $data['cabinet_section_id'] = $sectionId;
        }

        return $data;
    }
}
