<?php

namespace App\Services;

use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductCombination;
use Illuminate\Support\Collection;

class ProductMatcher
{
    /**
     * Match extracted equipment to existing products
     *
     * @param array $equipment - ['brand' => 'SubZero', 'model' => 'DET3650RID']
     * @return Product|ProductCombination|null
     */
    public function matchEquipment(array $equipment): ?Product
    {
        $brand = $equipment['brand'] ?? '';
        $model = $equipment['model'] ?? '';

        if (empty($brand) && empty($model)) {
            return null;
        }

        // Try to find product by name containing brand and model
        $query = Product::query();

        // Search patterns
        $patterns = [
            "{$brand} {$model}",
            "{$brand}-{$model}",
            "{$brand}_{$model}",
            "{$model}",
        ];

        foreach ($patterns as $pattern) {
            $product = (clone $query)
                ->where('name', 'LIKE', "%{$pattern}%")
                ->orWhere('reference', 'LIKE', "%{$pattern}%")
                ->first();

            if ($product) {
                return $product;
            }
        }

        // Try partial matches
        if (!empty($brand)) {
            $product = (clone $query)
                ->where('name', 'LIKE', "%{$brand}%")
                ->where('name', 'LIKE', "%{$model}%")
                ->first();

            if ($product) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Batch match multiple equipment items
     *
     * @param array $equipmentList
     * @return array ['matched' => [...], 'unmatched' => [...]]
     */
    public function batchMatch(array $equipmentList): array
    {
        $matched = [];
        $unmatched = [];

        foreach ($equipmentList as $equipment) {
            $product = $this->matchEquipment($equipment);

            if ($product) {
                $matched[] = [
                    'equipment' => $equipment,
                    'product' => $product,
                ];
            } else {
                $unmatched[] = $equipment;
            }
        }

        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Format equipment string for display/storage
     */
    public function formatEquipmentString(array $equipment): string
    {
        $brand = $equipment['brand'] ?? '';
        $model = $equipment['model'] ?? '';

        return trim("{$brand} {$model}");
    }
}
