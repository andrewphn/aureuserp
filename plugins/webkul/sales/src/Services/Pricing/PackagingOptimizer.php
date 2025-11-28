<?php

namespace Webkul\Sale\Services\Pricing;

use Webkul\Product\Models\Packaging;

/**
 * Packaging Optimizer Service
 *
 * Finds the best packaging configuration for a given product quantity.
 * Extracted from QuotationResource.php:2100 during Phase 1 refactoring.
 *
 * @note Packaging model exists but currently has no data. Once packaging data
 *       is populated, this service will automatically start returning optimal packaging.
 */
class PackagingOptimizer
{
    /**
     * Find the optimal packaging for a given product and quantity
     *
     * Searches for the largest packaging size that evenly divides into the quantity.
     * For example, if ordering 100 units and packagings exist for 1, 10, 25, 50:
     * - Will return the 50-unit packaging (2 packages needed)
     *
     * @param int $productId Product ID
     * @param float $quantity Desired quantity
     * @return array|null Array with packaging_id and packaging_qty, or null if no match
     */
    public static function findOptimal($productId, $quantity): ?array
    {
        $packagings = Packaging::where('product_id', $productId)
            ->orderByDesc('qty')
            ->get();

        foreach ($packagings as $packaging) {
            if ($quantity && $quantity % $packaging->qty == 0) {
                return [
                    'packaging_id'  => $packaging->id,
                    'packaging_qty' => round($quantity / $packaging->qty, 2),
                ];
            }
        }

        return null;
    }
}
