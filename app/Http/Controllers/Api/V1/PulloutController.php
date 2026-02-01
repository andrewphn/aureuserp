<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\Pullout;

class PulloutController extends BaseResourceController
{
    protected string $modelClass = Pullout::class;

    protected array $searchableFields = ['pullout_code', 'notes'];

    protected array $filterableFields = [
        'id',
        'cabinet_section_id',
        'pullout_type',
        'slide_product_id',
    ];

    protected array $sortableFields = [
        'id',
        'pullout_code',
        'position_from_top',
        'width_inches',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'cabinetSection',
        'slideProduct',
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
            'pullout_code' => 'nullable|string|max:50',
            'pullout_type' => 'nullable|string|max:50',
            'position_from_top' => 'nullable|integer|min:0',
            'width_inches' => 'nullable|numeric|min:0',
            'height_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'nullable|numeric|min:0',
            'slide_product_id' => 'nullable|integer|exists:products_products,id',
            'notes' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'pullout_code' => 'sometimes|string|max:50',
            'pullout_type' => 'nullable|string|max:50',
            'position_from_top' => 'nullable|integer|min:0',
            'width_inches' => 'nullable|numeric|min:0',
            'height_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'nullable|numeric|min:0',
            'slide_product_id' => 'nullable|integer|exists:products_products,id',
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
