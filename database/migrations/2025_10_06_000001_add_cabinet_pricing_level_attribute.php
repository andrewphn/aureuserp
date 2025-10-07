<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Pricing Level attribute to Cabinet product with 5 pricing tiers.
     * Base price is Level 2 ($168/LF), extra_price adjusts from this base.
     */
    public function up(): void
    {
        $now = now();

        // Create Pricing Level attribute
        DB::table('products_attributes')->insert([
            'name' => 'Pricing Level',
            'creator_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $pricingLevelAttrId = DB::getPdo()->lastInsertId();

        // Create 5 pricing level options
        // Base price is Level 2 ($168), so extra_price is difference from $168
        $pricingLevels = [
            ['name' => 'Level 1 - Basic ($138/LF)', 'base_price' => 138, 'extra_price' => -30, 'description' => 'Paint grade, open boxes only, no doors/drawers'],
            ['name' => 'Level 2 - Standard ($168/LF)', 'base_price' => 168, 'extra_price' => 0, 'description' => 'Paint grade, semi-European, flat/shaker doors'],
            ['name' => 'Level 3 - Enhanced ($192/LF)', 'base_price' => 192, 'extra_price' => 24, 'description' => 'Stain grade, semi-complicated paint grade'],
            ['name' => 'Level 4 - Premium ($210/LF)', 'base_price' => 210, 'extra_price' => 42, 'description' => 'Beaded frames, specialty doors, moldings'],
            ['name' => 'Level 5 - Custom ($225/LF)', 'base_price' => 225, 'extra_price' => 57, 'description' => 'Unique custom work, paneling, reeded, rattan'],
        ];

        foreach ($pricingLevels as $level) {
            DB::table('products_attribute_options')->insert([
                'attribute_id' => $pricingLevelAttrId,
                'name' => $level['name'],
                'extra_price' => $level['extra_price'],
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        echo "Created Pricing Level attribute with 5 options\n";

        // Get Cabinet product
        $cabinetProduct = DB::table('products_products')
            ->where('reference', 'CABINET')
            ->first();

        if (!$cabinetProduct) {
            echo "Cabinet product not found, skipping attribute mapping\n";
            return;
        }

        // Map Pricing Level attribute to Cabinet product
        $existingMapping = DB::table('products_product_attributes')
            ->where('product_id', $cabinetProduct->id)
            ->where('attribute_id', $pricingLevelAttrId)
            ->exists();

        if (!$existingMapping) {
            // Get current max sort order
            $maxSort = DB::table('products_product_attributes')
                ->where('product_id', $cabinetProduct->id)
                ->max('sort') ?? 0;

            DB::table('products_product_attributes')->insert([
                'sort' => $maxSort + 1,
                'product_id' => $cabinetProduct->id,
                'attribute_id' => $pricingLevelAttrId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            echo "✓ Mapped Pricing Level attribute to Cabinet product\n";
        }

        // Get the product_attribute_id from the mapping table
        $productAttributeId = DB::table('products_product_attributes')
            ->where('product_id', $cabinetProduct->id)
            ->where('attribute_id', $pricingLevelAttrId)
            ->value('id');

        // Populate attribute values (link all 5 pricing levels to Cabinet product)
        $options = DB::table('products_attribute_options')
            ->where('attribute_id', $pricingLevelAttrId)
            ->get(['id', 'extra_price']);

        $valuesToInsert = [];
        foreach ($options as $option) {
            $exists = DB::table('products_product_attribute_values')
                ->where('product_id', $cabinetProduct->id)
                ->where('attribute_id', $pricingLevelAttrId)
                ->where('attribute_option_id', $option->id)
                ->exists();

            if (!$exists) {
                $valuesToInsert[] = [
                    'product_id' => $cabinetProduct->id,
                    'attribute_id' => $pricingLevelAttrId,
                    'product_attribute_id' => $productAttributeId,
                    'attribute_option_id' => $option->id,
                    'extra_price' => $option->extra_price,
                ];
            }
        }

        if (!empty($valuesToInsert)) {
            DB::table('products_product_attribute_values')->insert($valuesToInsert);
            echo "✓ Populated " . count($valuesToInsert) . " pricing level values\n";
        }

        echo "\n✓ Cabinet product now has Pricing Level attribute with 5 tiers\n";
        echo "✓ PDF parsing can now map 'Tier 2' to 'Level 2', 'Tier 4' to 'Level 4', etc.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $pricingLevelAttr = DB::table('products_attributes')
            ->where('name', 'Pricing Level')
            ->first();

        if ($pricingLevelAttr) {
            // Delete attribute values
            DB::table('products_product_attribute_values')
                ->where('attribute_id', $pricingLevelAttr->id)
                ->delete();

            // Delete attribute mapping
            DB::table('products_product_attributes')
                ->where('attribute_id', $pricingLevelAttr->id)
                ->delete();

            // Delete attribute options
            DB::table('products_attribute_options')
                ->where('attribute_id', $pricingLevelAttr->id)
                ->delete();

            // Delete attribute
            DB::table('products_attributes')
                ->where('id', $pricingLevelAttr->id)
                ->delete();

            echo "Removed Pricing Level attribute\n";
        }
    }
};
