<?php

use Illuminate\Database\Migrations\Migration;
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
     * TCS Cabinet Pricing Structure:
     * - Base Level: $138-$225 (already configured)
     * - Material Category: $138-$185 additive pricing
     * - Finish Option: $0-$255/LF additive pricing
     *
     * Total Price = Base Level + Material Category + Finish Option
     */
    public function up(): void
    {
        $now = now();

        // Create "Material Category" attribute
        $materialCategoryId = DB::table('products_attributes')->insertGetId([
            'name' => 'Material Category',
            'type' => 'radio',
            'sort' => 20,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Add Material Category options with TCS pricing
        $materialOptions = [
            ['name' => 'Paint Grade (Hard Maple/Poplar)', 'extra_price' => 138.00, 'sort' => 1],
            ['name' => 'Stain Grade (Oak/Maple)', 'extra_price' => 156.00, 'sort' => 2],
            ['name' => 'Premium (Rifted White Oak/Black Walnut)', 'extra_price' => 185.00, 'sort' => 3],
            ['name' => 'Custom/Exotic (Price TBD)', 'extra_price' => 0.00, 'sort' => 4],
        ];

        foreach ($materialOptions as $option) {
            DB::table('products_attribute_options')->insert([
                'name' => $option['name'],
                'extra_price' => $option['extra_price'],
                'sort' => $option['sort'],
                'attribute_id' => $materialCategoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create "Finish Option" attribute
        $finishOptionId = DB::table('products_attributes')->insertGetId([
            'name' => 'Finish Option',
            'type' => 'radio',
            'sort' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Add Finish Option options with TCS pricing (per LF)
        $finishOptions = [
            ['name' => 'Unfinished', 'extra_price' => 0.00, 'sort' => 1],
            ['name' => 'Prime Only', 'extra_price' => 60.00, 'sort' => 2],
            ['name' => 'Prime + Paint', 'extra_price' => 118.00, 'sort' => 3],
            ['name' => 'Custom Color', 'extra_price' => 125.00, 'sort' => 4],
            ['name' => 'Clear Coat', 'extra_price' => 95.00, 'sort' => 5],
            ['name' => 'Stain + Clear', 'extra_price' => 213.00, 'sort' => 6],
            ['name' => 'Color Match Stain + Clear', 'extra_price' => 255.00, 'sort' => 7],
            ['name' => 'Two-tone', 'extra_price' => 235.00, 'sort' => 8],
        ];

        foreach ($finishOptions as $option) {
            DB::table('products_attribute_options')->insert([
                'name' => $option['name'],
                'extra_price' => $option['extra_price'],
                'sort' => $option['sort'],
                'attribute_id' => $finishOptionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Link new attributes to Cabinet product (reference: CABINET)
        $cabinetProduct = DB::table('products_products')
            ->where('reference', 'CABINET')
            ->first(['id']);

        if ($cabinetProduct) {
            // Link Material Category attribute
            DB::table('products_product_attributes')->insert([
                'product_id' => $cabinetProduct->id,
                'attribute_id' => $materialCategoryId,
                'sort' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Link Finish Option attribute
            DB::table('products_product_attributes')->insert([
                'product_id' => $cabinetProduct->id,
                'attribute_id' => $finishOptionId,
                'sort' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            echo "✅ Added Material Category and Finish Option attributes to Cabinet product (ID: {$cabinetProduct->id})\n";
        } else {
            echo "⚠️  Warning: Cabinet product with reference 'CABINET' not found. Attributes created but not linked.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get attribute IDs
        $materialCategoryId = DB::table('products_attributes')
            ->where('name', 'Material Category')
            ->value('id');

        $finishOptionId = DB::table('products_attributes')
            ->where('name', 'Finish Option')
            ->value('id');

        if ($materialCategoryId) {
            // Remove product-attribute links
            DB::table('products_product_attributes')
                ->where('attribute_id', $materialCategoryId)
                ->delete();

            // Remove attribute options
            DB::table('products_attribute_options')
                ->where('attribute_id', $materialCategoryId)
                ->delete();

            // Remove attribute
            DB::table('products_attributes')
                ->where('id', $materialCategoryId)
                ->delete();
        }

        if ($finishOptionId) {
            // Remove product-attribute links
            DB::table('products_product_attributes')
                ->where('attribute_id', $finishOptionId)
                ->delete();

            // Remove attribute options
            DB::table('products_attribute_options')
                ->where('attribute_id', $finishOptionId)
                ->delete();

            // Remove attribute
            DB::table('products_attributes')
                ->where('id', $finishOptionId)
                ->delete();
        }
    }
};
