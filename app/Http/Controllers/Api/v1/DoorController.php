<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\Door;

class DoorController extends BaseResourceController
{
    protected string $modelClass = Door::class;

    protected array $searchableFields = ['door_code', 'notes'];

    protected array $filterableFields = [
        'id',
        'cabinet_section_id',
        'door_style',
        'door_type',
        'hinge_side',
        'hinge_product_id',
    ];

    protected array $sortableFields = [
        'id',
        'door_code',
        'width_inches',
        'height_inches',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'cabinetSection',
        'hingeProduct',
    ];

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
            'door_code' => 'nullable|string|max:50',
            'door_style' => 'nullable|string|max:100',
            'door_type' => 'nullable|string|max:50',
            'hinge_side' => 'nullable|string|in:left,right,top,bottom',
            'width_inches' => 'nullable|numeric|min:0',
            'height_inches' => 'nullable|numeric|min:0',
            'hinge_product_id' => 'nullable|integer|exists:products_products,id',
            'notes' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'door_code' => 'sometimes|string|max:50',
            'door_style' => 'nullable|string|max:100',
            'door_type' => 'nullable|string|max:50',
            'hinge_side' => 'nullable|string|in:left,right,top,bottom',
            'width_inches' => 'nullable|numeric|min:0',
            'height_inches' => 'nullable|numeric|min:0',
            'hinge_product_id' => 'nullable|integer|exists:products_products,id',
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
