<?php

namespace Webkul\Sale\Services\Pricing;

use Webkul\Account\Facades\Tax;

/**
 * Line Totals Calculator Service
 *
 * Calculates order line item totals including subtotal, tax, and margin.
 * Extracted from QuotationResource.php:2092 during Phase 2 refactoring.
 *
 * This service handles the core business logic for calculating:
 * - Subtotal (price * quantity - discount)
 * - Tax amounts
 * - Total (subtotal + tax)
 * - Margin and margin percentage
 */
class LineTotalsCalculator
{
    /**
     * Calculate all line item totals
     *
     * @param float $priceUnit Unit price
     * @param float $quantity Number of units
     * @param float $purchasePrice Cost/purchase price per unit
     * @param float $discount Discount percentage (0-100)
     * @param array $taxIds Array of tax IDs to apply
     * @return array Associative array with all calculated values
     */
    public static function calculate(
        float $priceUnit,
        float $quantity,
        float $purchasePrice = 0,
        float $discount = 0,
        array $taxIds = []
    ): array {
        // Calculate subtotal before tax
        $subTotal = $priceUnit * $quantity;

        // Apply discount if present
        if ($discount > 0) {
            $discountAmount = $subTotal * ($discount / 100);
            $subTotal -= $discountAmount;
        }

        // Calculate tax using the Tax facade
        [$subTotal, $taxAmount] = Tax::collect($taxIds, $subTotal, $quantity);

        // Calculate total (subtotal + tax)
        $total = $subTotal + $taxAmount;

        // Calculate margin using MarginCalculator
        [$margin, $marginPercentage] = MarginCalculator::calculate(
            $priceUnit,
            $purchasePrice,
            $quantity,
            $discount
        );

        return [
            'price_subtotal' => round($subTotal, 4),
            'price_tax' => round($taxAmount, 4),
            'price_total' => round($total, 4),
            'margin' => round($margin, 4),
            'margin_percent' => round($marginPercentage, 4),
        ];
    }

    /**
     * Get empty/zero totals for when no product is selected
     *
     * @return array Associative array with all zero values
     */
    public static function getEmptyTotals(): array
    {
        return [
            'price_unit' => 0,
            'discount' => 0,
            'price_tax' => 0,
            'price_subtotal' => 0,
            'price_total' => 0,
            'purchase_price' => 0,
            'margin' => 0,
            'margin_percent' => 0,
        ];
    }
}
