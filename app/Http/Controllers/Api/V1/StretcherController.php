<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webkul\Project\Models\Stretcher;

class StretcherController extends BaseResourceController
{
    protected string $modelClass = Stretcher::class;

    protected array $searchableFields = ['notes'];

    protected array $filterableFields = [
        'id',
        'cabinet_id',
        'stretcher_type',
        'position',
    ];

    protected array $sortableFields = [
        'id',
        'position',
        'width_inches',
        'height_inches',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = ['cabinet'];

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
            'stretcher_type' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:50',
            'width_inches' => 'nullable|numeric|min:0',
            'height_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'stretcher_type' => 'sometimes|string|max:50',
            'position' => 'nullable|string|max:50',
            'width_inches' => 'nullable|numeric|min:0',
            'height_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'nullable|numeric|min:0',
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
