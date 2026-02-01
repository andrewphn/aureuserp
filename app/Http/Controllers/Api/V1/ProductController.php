<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Webkul\Product\Models\Product;

class ProductController extends BaseResourceController
{
    protected string $modelClass = Product::class;

    protected array $searchableFields = [
        'name',
        'sku',
        'barcode',
        'description',
        'internal_reference',
    ];

    protected array $filterableFields = [
        'id',
        'type',
        'category_id',
        'is_active',
        'is_storable',
        'is_purchasable',
        'is_saleable',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'sku',
        'list_price',
        'cost_price',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'category',
        'uom',
        'purchaseUom',
    ];

    protected function validateStore(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products_products,sku',
            'barcode' => 'nullable|string|max:100|unique:products_products,barcode',
            'type' => 'nullable|string|max:50',
            'category_id' => 'nullable|integer|exists:products_categories,id',
            'description' => 'nullable|string',
            'list_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_storable' => 'nullable|boolean',
            'is_purchasable' => 'nullable|boolean',
            'is_saleable' => 'nullable|boolean',
            'uom_id' => 'nullable|integer|exists:products_uoms,id',
        ];
    }

    protected function validateUpdate(): array
    {
        $productId = request()->route('product') ?? request()->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products_products,sku,' . $productId,
            'barcode' => 'nullable|string|max:100|unique:products_products,barcode,' . $productId,
            'type' => 'nullable|string|max:50',
            'category_id' => 'nullable|integer|exists:products_categories,id',
            'description' => 'nullable|string',
            'list_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_storable' => 'nullable|boolean',
            'is_purchasable' => 'nullable|boolean',
            'is_saleable' => 'nullable|boolean',
            'uom_id' => 'nullable|integer|exists:products_uoms,id',
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
