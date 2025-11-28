<?php

namespace Webkul\Sale\Services\Pricing;

/**
 * Margin Calculator Service
 *
 * Calculates profit margins based on selling price, cost price, quantity, and discount.
 * Extracted from QuotationResource.php:2148 during Phase 1 refactoring.
 */
class MarginCalculator
{
    /**
     * Calculate margin and margin percentage
     *
     * @param float $sellingPrice Unit selling price
     * @param float $costPrice Unit cost/purchase price
     * @param float $quantity Number of units
     * @param float $discount Discount percentage (0-100)
     * @return array [totalMargin, marginPercentage]
     *
     * @example
     * // Selling at $100/unit, cost $60/unit, 10 units, 10% discount
     * // Discounted price = $100 - ($100 * 0.10) = $90
     * // Margin per unit = $90 - $60 = $30
     * // Total margin = $30 * 10 = $300
     * // Margin % = ($30 / $90) * 100 = 33.33%
     * MarginCalculator::calculate(100, 60, 10, 10); // Returns [300, 33.33]
     */
    public static function calculate($sellingPrice, $costPrice, $quantity, $discount = 0): array
    {
        // Apply discount to selling price
        $discountedPrice = $sellingPrice - ($sellingPrice * ($discount / 100));

        // Calculate margin per unit
        $marginPerUnit = $discountedPrice - $costPrice;

        // Calculate total margin
        $totalMargin = $marginPerUnit * $quantity;

        // Calculate margin percentage (avoid division by zero)
        if ($discountedPrice != 0 && $marginPerUnit != 0) {
            $marginPercentage = ($marginPerUnit / $discountedPrice) * 100;
        } else {
            $marginPercentage = 0;
        }

        return [
            $totalMargin,
            $marginPercentage,
        ];
    }
}
