<?php

namespace App\Http\Controllers\Api\V1;

use Webkul\Inventory\Models\Warehouse;

class WarehouseController extends BaseResourceController
{
    protected string $modelClass = Warehouse::class;

    protected array $searchableFields = [
        'name',
        'code',
    ];

    protected array $filterableFields = [
        'id',
        'company_id',
        'is_active',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'code',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'company',
        'locations',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:20',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'is_active' => 'nullable|boolean',
        ];
    }
}
