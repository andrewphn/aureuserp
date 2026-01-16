<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Inventory\Models\Location;

class LocationController extends BaseResourceController
{
    protected string $modelClass = Location::class;

    protected array $searchableFields = [
        'name',
        'complete_name',
        'barcode',
    ];

    protected array $filterableFields = [
        'id',
        'warehouse_id',
        'parent_id',
        'type',
        'is_scrap',
        'is_replenish',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'complete_name',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'warehouse',
        'parent',
        'children',
    ];

    protected function getBaseQuery(): Builder
    {
        $query = parent::getBaseQuery();

        if ($warehouseId = request()->route('warehouse')) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query;
    }

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'warehouse_id' => 'nullable|integer|exists:inventories_warehouses,id',
            'parent_id' => 'nullable|integer|exists:inventories_locations,id',
            'type' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:100',
            'is_scrap' => 'nullable|boolean',
            'is_replenish' => 'nullable|boolean',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'warehouse_id' => 'nullable|integer|exists:inventories_warehouses,id',
            'parent_id' => 'nullable|integer|exists:inventories_locations,id',
            'type' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:100',
            'is_scrap' => 'nullable|boolean',
            'is_replenish' => 'nullable|boolean',
        ];
    }
}
