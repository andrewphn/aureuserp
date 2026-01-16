<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\Faceframe;

class FaceframeController extends BaseResourceController
{
    protected string $modelClass = Faceframe::class;

    protected array $searchableFields = ['notes'];

    protected array $filterableFields = [
        'id',
        'cabinet_id',
        'material_product_id',
    ];

    protected array $sortableFields = [
        'id',
        'stile_width_inches',
        'rail_width_inches',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'cabinet',
        'materialProduct',
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
            'stile_width_inches' => 'nullable|numeric|min:0',
            'rail_width_inches' => 'nullable|numeric|min:0',
            'mid_stile_count' => 'nullable|integer|min:0',
            'material_product_id' => 'nullable|integer|exists:products_products,id',
            'notes' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'stile_width_inches' => 'sometimes|numeric|min:0',
            'rail_width_inches' => 'nullable|numeric|min:0',
            'mid_stile_count' => 'nullable|integer|min:0',
            'material_product_id' => 'nullable|integer|exists:products_products,id',
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
