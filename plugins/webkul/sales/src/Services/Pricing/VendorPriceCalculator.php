<?php

namespace Webkul\Sale\Services\Pricing;

use Webkul\Sale\Models\Product;
use Webkul\Support\Models\UOM;

/**
 * Vendor Price Calculator Service
 *
 * Calculates the unit price for a product based on vendor/supplier pricing rules.
 * Extracted from QuotationResource.php:2066 during Phase 2 refactoring.
 *
 * This service handles vendor price lookup with:
 * - Partner-specific pricing
 * - Minimum quantity thresholds
 * - Currency-specific prices
 * - UOM factor adjustments
 */
class VendorPriceCalculator
{
    /**
     * Calculate the unit price for a product based on vendor pricing rules
     *
     * @param int $productId Product ID
     * @param int|null $partnerId Partner/vendor ID for partner-specific pricing
     * @param float $quantity Quantity for min_qty threshold checking
     * @param int|null $currencyId Currency ID to filter vendor prices
     * @param int|null $uomId Unit of Measure ID for price adjustment
     * @return float Calculated unit price
     */
    public static function calculate(
        int $productId,
        ?int $partnerId = null,
        float $quantity = 1,
        ?int $currencyId = null,
        ?int $uomId = null
    ): float {
        $product = Product::withTrashed()->find($productId);

        if (! $product) {
            return 0;
        }

        // Get vendor prices sorted by priority
        $vendorPrices = $product->supplierInformation->sortByDesc('sort');

        // Filter by partner if specified
        if ($partnerId) {
            $vendorPrices = $vendorPrices->where('partner_id', $partnerId);
        }

        // Filter by min quantity threshold and currency
        $vendorPrices = $vendorPrices
            ->where('min_qty', '<=', $quantity)
            ->when($currencyId, fn ($collection) => $collection->where('currency_id', $currencyId));

        // Get vendor price or fall back to product price/cost
        if (! $vendorPrices->isEmpty()) {
            $vendorPrice = $vendorPrices->first()->price;
        } else {
            $vendorPrice = $product->price ?? $product->cost ?? 0;
        }

        // Adjust for UOM factor if specified
        if ($uomId) {
            $uom = UOM::find($uomId);
            if ($uom && $uom->factor > 0) {
                return (float) ($vendorPrice / $uom->factor);
            }
        }

        return (float) $vendorPrice;
    }
}
