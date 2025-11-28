<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     *
     * Seeds TCS Cabinet Product Attributes based on:
     * - cabinet_research.md (10-level hierarchical system)
     * - TCS Wholesale Pricing Sheets (Jan 2025)
     * - cabinet-product-attributes-mapping.md
     */
    public function up(): void
    {
        $now = now();

        // 1. CONSTRUCTION STYLE (already exists, but we'll ensure it)
        $constructionStyle = DB::table('products_attributes')->where('name', 'Construction Style')->first();
        if (!$constructionStyle) {
            DB::table('products_attributes')->insert([
                'name' => 'Construction Style',
                'type' => 'radio',
                'sort' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $constructionStyleId = DB::getPdo()->lastInsertId();

            DB::table('products_attribute_options')->insert([
                ['name' => 'Face Frame Traditional', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $constructionStyleId, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Frameless Euro-Style', 'extra_price' => 15, 'sort' => 2, 'attribute_id' => $constructionStyleId, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // 2. DOOR/DRAWER STYLE (from cabinet_research.md Level 6)
        DB::table('products_attributes')->insert([
            'name' => 'Door Style',
            'type' => 'radio',
            'sort' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $doorStyleId = DB::getPdo()->lastInsertId();

        DB::table('products_attribute_options')->insert([
            ['name' => 'Slab (Flat Panel)', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Shaker (5-Piece Frame)', 'extra_price' => 12, 'sort' => 2, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Raised Panel Square', 'extra_price' => 18, 'sort' => 3, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Raised Panel Arch', 'extra_price' => 22, 'sort' => 4, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Recessed Panel Square', 'extra_price' => 15, 'sort' => 5, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Glass Panel Single Lite', 'extra_price' => 25, 'sort' => 6, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Beadboard Panel', 'extra_price' => 20, 'sort' => 7, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'V-Groove Panel', 'extra_price' => 18, 'sort' => 8, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Louvered', 'extra_price' => 28, 'sort' => 9, 'attribute_id' => $doorStyleId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 3. PRIMARY MATERIAL / WOOD SPECIES (from TCS pricing + cabinet_research.md)
        DB::table('products_attributes')->insert([
            'name' => 'Primary Material',
            'type' => 'select',
            'sort' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $materialId = DB::getPdo()->lastInsertId();

        DB::table('products_attribute_options')->insert([
            // Paint Grade
            ['name' => 'MDF (Paint Grade)', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hard Maple (Paint Grade)', 'extra_price' => 10, 'sort' => 2, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Poplar (Paint Grade)', 'extra_price' => 8, 'sort' => 3, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
            // Stain Grade
            ['name' => 'Red Oak (Stain Grade)', 'extra_price' => 18, 'sort' => 4, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hard Maple (Stain Grade)', 'extra_price' => 22, 'sort' => 5, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cherry (Premium)', 'extra_price' => 35, 'sort' => 6, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
            // Premium
            ['name' => 'Rifted White Oak (Premium)', 'extra_price' => 45, 'sort' => 7, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Quarter Sawn White Oak (Premium)', 'extra_price' => 50, 'sort' => 8, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Black Walnut (Premium)', 'extra_price' => 60, 'sort' => 9, 'attribute_id' => $materialId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 4. FINISH TYPE (from TCS pricing)
        DB::table('products_attributes')->insert([
            'name' => 'Finish Type',
            'type' => 'radio',
            'sort' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $finishId = DB::getPdo()->lastInsertId();

        DB::table('products_attribute_options')->insert([
            ['name' => 'Paint Grade', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $finishId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Stain Grade', 'extra_price' => 15, 'sort' => 2, 'attribute_id' => $finishId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Clear Coat (Natural)', 'extra_price' => 12, 'sort' => 3, 'attribute_id' => $finishId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 5. EDGE PROFILE (from cabinet_research.md Level 6)
        DB::table('products_attributes')->insert([
            'name' => 'Edge Profile',
            'type' => 'select',
            'sort' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $edgeProfileId = DB::getPdo()->lastInsertId();

        DB::table('products_attribute_options')->insert([
            ['name' => 'Square / Straight (No Profile)', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $edgeProfileId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Roundover 1/8"', 'extra_price' => 2, 'sort' => 2, 'attribute_id' => $edgeProfileId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Roundover 1/4"', 'extra_price' => 3, 'sort' => 3, 'attribute_id' => $edgeProfileId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Chamfer 1/8"', 'extra_price' => 2, 'sort' => 4, 'attribute_id' => $edgeProfileId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Chamfer 1/4"', 'extra_price' => 3, 'sort' => 5, 'attribute_id' => $edgeProfileId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ogee', 'extra_price' => 5, 'sort' => 6, 'attribute_id' => $edgeProfileId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cove', 'extra_price' => 4, 'sort' => 7, 'attribute_id' => $edgeProfileId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 6. DRAWER BOX CONSTRUCTION (from cabinet_research.md Level 7)
        DB::table('products_attributes')->insert([
            'name' => 'Drawer Box Construction',
            'type' => 'select',
            'sort' => 6,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $drawerBoxId = DB::getPdo()->lastInsertId();

        DB::table('products_attribute_options')->insert([
            ['name' => 'Baltic Birch Plywood (Standard)', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $drawerBoxId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dovetail Solid Wood', 'extra_price' => 15, 'sort' => 2, 'attribute_id' => $drawerBoxId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Undermount Soft-Close Ready', 'extra_price' => 8, 'sort' => 3, 'attribute_id' => $drawerBoxId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 7. OVERLAY TYPE (from cabinet_research.md Level 6)
        DB::table('products_attributes')->insert([
            'name' => 'Door Overlay Type',
            'type' => 'radio',
            'sort' => 7,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $overlayId = DB::getPdo()->lastInsertId();

        DB::table('products_attribute_options')->insert([
            ['name' => 'Full Overlay (Modern)', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $overlayId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Partial Overlay (Traditional)', 'extra_price' => 0, 'sort' => 2, 'attribute_id' => $overlayId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Inset (Flush Premium)', 'extra_price' => 25, 'sort' => 3, 'attribute_id' => $overlayId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 8. CARCASS MATERIAL (from cabinet_research.md Level 4)
        DB::table('products_attributes')->insert([
            'name' => 'Box Material',
            'type' => 'select',
            'sort' => 8,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $carcassId = DB::getPdo()->lastInsertId();

        DB::table('products_attribute_options')->insert([
            ['name' => '3/4" Plywood (Standard)', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $carcassId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '3/4" MDF', 'extra_price' => 3, 'sort' => 2, 'attribute_id' => $carcassId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Baltic Birch Plywood', 'extra_price' => 8, 'sort' => 3, 'attribute_id' => $carcassId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        echo "✓ Successfully seeded " . DB::table('products_attributes')->count() . " cabinet product attributes\n";
        echo "✓ Successfully seeded " . DB::table('products_attribute_options')->count() . " attribute options\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all attribute options for these specific attributes
        $attributeNames = [
            'Construction Style',
            'Door Style',
            'Primary Material',
            'Finish Type',
            'Edge Profile',
            'Drawer Box Construction',
            'Door Overlay Type',
            'Box Material'
        ];

        $attributeIds = DB::table('products_attributes')
            ->whereIn('name', $attributeNames)
            ->pluck('id');

        DB::table('products_attribute_options')
            ->whereIn('attribute_id', $attributeIds)
            ->delete();

        DB::table('products_attributes')
            ->whereIn('name', $attributeNames)
            ->delete();
    }
};
