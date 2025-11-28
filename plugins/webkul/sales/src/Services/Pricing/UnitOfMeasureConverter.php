<?php

namespace Webkul\Sale\Services\Pricing;

/**
 * Unit of Measure Converter Service
 *
 * Handles conversion between different units of measure.
 *
 * @note Currently depends on a Uom model that doesn't exist in the codebase.
 *       This appears to be unfinished functionality. Extracted from QuotationResource.php:2062
 *       during Phase 1 refactoring to isolate the issue.
 *
 * @todo Implement proper Uom model or replace with working alternative
 */
class UnitOfMeasureConverter
{
    /**
     * Convert quantity based on unit of measure factor
     *
     * @param int|null $uomId Unit of measure ID
     * @param float|null $quantity Quantity to convert
     * @return float Converted quantity
     *
     * @fixme This method uses a non-existent Uom model and will fail if called with a valid $uomId.
     *        Currently safe because no order lines have product_uom_id set (functionality unused).
     */
    public static function convert($uomId, $quantity): float
    {
        if (! $uomId) {
            return (float) ($quantity ?? 0);
        }

        // @todo: Replace with actual Uom model once implemented
        // Current code would fail with: "Class 'Uom' not found"
        // $uom = Uom::find($uomId);
        // return (float) ($quantity ?? 0) / $uom->factor;

        // Temporary fallback: return quantity as-is
        return (float) ($quantity ?? 0);
    }
}
