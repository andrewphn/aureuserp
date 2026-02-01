<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Webkul\Inventory\Models\Move;

class MoveController extends BaseResourceController
{
    protected string $modelClass = Move::class;

    protected array $searchableFields = [
        'name',
        'reference',
    ];

    protected array $filterableFields = [
        'id',
        'product_id',
        'source_location_id',
        'destination_location_id',
        'operation_id',
        'state',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'scheduled_at',
        'quantity',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'product',
        'sourceLocation',
        'destinationLocation',
        'operation',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'product_id' => 'required|integer|exists:products_products,id',
            'source_location_id' => 'required|integer|exists:inventories_locations,id',
            'destination_location_id' => 'required|integer|exists:inventories_locations,id',
            'quantity' => 'required|numeric|min:0.01',
            'operation_id' => 'nullable|integer|exists:inventories_operations,id',
            'scheduled_at' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'product_id' => 'sometimes|integer|exists:products_products,id',
            'source_location_id' => 'sometimes|integer|exists:inventories_locations,id',
            'destination_location_id' => 'sometimes|integer|exists:inventories_locations,id',
            'quantity' => 'sometimes|numeric|min:0.01',
            'scheduled_at' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        return $data;
    }
}
