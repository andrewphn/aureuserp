<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes vertical clearances to match Blum TANDEM 563H Drawer Specifications diagram.
 * 
 * From the official diagram (page 2):
 * - Minimum top clearance: 6mm (1/4")
 * - Bottom clearance: 14mm (9/16")
 * - Maximum drawer height = opening minus 20mm (25/32")
 * 
 * Previous incorrect values:
 * - Top: 0.75" (19mm) - WRONG
 * - Bottom: 0.375" (9.5mm) - WRONG
 */
return new class extends Migration
{
    public function up(): void
    {
        // Correct clearances from Blum diagram
        $correctClearances = [
            'Slide Top Clearance' => 0.25,      // 6mm = 1/4"
            'Slide Bottom Clearance' => 0.5625, // 14mm = 9/16"
        ];

        $attributes = DB::table('products_attributes')
            ->whereIn('name', array_keys($correctClearances))
            ->pluck('id', 'name');

        // Get all slide product IDs
        $slideProductIds = DB::table('products_product_attribute_values as pav')
            ->join('products_attributes as a', 'pav.attribute_id', '=', 'a.id')
            ->where('a.name', 'Slide Length')
            ->pluck('pav.product_id')
            ->unique();

        // Update each slide product
        foreach ($slideProductIds as $productId) {
            foreach ($correctClearances as $attrName => $value) {
                $attrId = $attributes[$attrName] ?? null;
                if (!$attrId) continue;

                DB::table('products_product_attribute_values')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attrId)
                    ->update(['numeric_value' => $value]);
            }
        }
    }

    public function down(): void
    {
        // Revert to previous (incorrect) values
        $previousClearances = [
            'Slide Top Clearance' => 0.75,
            'Slide Bottom Clearance' => 0.375,
        ];

        $attributes = DB::table('products_attributes')
            ->whereIn('name', array_keys($previousClearances))
            ->pluck('id', 'name');

        $slideProductIds = DB::table('products_product_attribute_values as pav')
            ->join('products_attributes as a', 'pav.attribute_id', '=', 'a.id')
            ->where('a.name', 'Slide Length')
            ->pluck('pav.product_id')
            ->unique();

        foreach ($slideProductIds as $productId) {
            foreach ($previousClearances as $attrName => $value) {
                $attrId = $attributes[$attrName] ?? null;
                if (!$attrId) continue;

                DB::table('products_product_attribute_values')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attrId)
                    ->update(['numeric_value' => $value]);
            }
        }
    }
};
