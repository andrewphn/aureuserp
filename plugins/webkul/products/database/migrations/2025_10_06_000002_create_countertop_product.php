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
     * Creates Countertop product with Square Foot UOM for millwork countertop services.
     */
    public function up(): void
    {
        $now = now();
        $creatorId = DB::table('users')->value('id'); // Get first user ID

        // Skip if no users exist yet
        if (!$creatorId) {
            echo "No users exist yet, skipping Countertop product creation\n";
            return;
        }

        // Get or create Square Foot UOM
        $sqftUom = DB::table('unit_of_measures')->where('name', 'Square Foot')->first();

        if (!$sqftUom) {
            // Surface category (ID: 5)
            $surfaceCategoryId = DB::table('unit_of_measure_categories')
                ->where('name', 'Surface')
                ->value('id');

            if (!$surfaceCategoryId) {
                DB::table('unit_of_measure_categories')->insert([
                    'name' => 'Surface',
                    'creator_id' => $creatorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $surfaceCategoryId = DB::getPdo()->lastInsertId();
            }

            DB::table('unit_of_measures')->insert([
                'name' => 'Square Foot',
                'category_id' => $surfaceCategoryId,
                'type' => 'bigger',
                'factor' => 1,
                'rounding' => 0.01,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sqftUomId = DB::getPdo()->lastInsertId();
            echo "Created Square Foot UOM (ID: {$sqftUomId})\n";
        } else {
            $sqftUomId = $sqftUom->id;
            echo "Square Foot UOM already exists (ID: {$sqftUomId})\n";
        }

        // Get category
        $categoryId = DB::table('products_categories')
            ->where('name', 'Woodwork Services')
            ->value('id');

        // Get TCS company
        $companyId = DB::table('companies')->where('acronym', 'TCS')->value('id')
            ?? DB::table('companies')->where('name', 'The Carpenter\'s Son')->value('id')
            ?? DB::table('companies')->value('id');

        // Skip if no company exists
        if (!$companyId) {
            echo "No company exists yet, skipping Countertop product creation\n";
            return;
        }

        // Create Countertop product
        $countertopProduct = [
            'name' => 'Millwork Countertop',
            'description' => 'Custom millwork countertops - configure material, finish, and edge profile.',
            'type' => 'service',
            'price' => 75.00, // Base price per SF (to be determined - placeholder)
            'cost' => 0,
            'reference' => 'COUNTERTOP',
            'category_id' => $categoryId,
            'company_id' => $companyId,
            'uom_id' => $sqftUomId,
            'uom_po_id' => $sqftUomId,
            'creator_id' => $creatorId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Check if product already exists
        $existingId = DB::table('products_products')
            ->where('reference', 'COUNTERTOP')
            ->value('id');

        if ($existingId) {
            echo "Product already exists: Millwork Countertop (ID: {$existingId})\n";
            $productId = $existingId;
        } else {
            DB::table('products_products')->insert($countertopProduct);
            $productId = DB::getPdo()->lastInsertId();
            echo "Created product: Millwork Countertop (ID: {$productId})\n";
        }

        // Get relevant attributes for countertops
        $attributes = DB::table('products_attributes')
            ->whereIn('name', [
                'Primary Material',
                'Finish Type',
                'Edge Profile',
            ])
            ->pluck('id', 'name')
            ->toArray();

        // Map attributes to Countertop product
        $attributeMappings = [];
        $sort = 1;
        foreach ($attributes as $attributeName => $attributeId) {
            $exists = DB::table('products_product_attributes')
                ->where('product_id', $productId)
                ->where('attribute_id', $attributeId)
                ->exists();

            if (!$exists) {
                $attributeMappings[] = [
                    'sort' => $sort++,
                    'product_id' => $productId,
                    'attribute_id' => $attributeId,
                    'creator_id' => $creatorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($attributeMappings)) {
            DB::table('products_product_attributes')->insert($attributeMappings);
            echo "  → Mapped " . count($attributeMappings) . " attributes to Countertop\n";
        }

        // Populate attribute values for each attribute
        foreach ($attributes as $attributeName => $attributeId) {
            $productAttributeId = DB::table('products_product_attributes')
                ->where('product_id', $productId)
                ->where('attribute_id', $attributeId)
                ->value('id');

            if (!$productAttributeId) {
                continue;
            }

            $options = DB::table('products_attribute_options')
                ->where('attribute_id', $attributeId)
                ->get(['id', 'extra_price']);

            $valuesToInsert = [];
            foreach ($options as $option) {
                $exists = DB::table('products_product_attribute_values')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attributeId)
                    ->where('attribute_option_id', $option->id)
                    ->exists();

                if (!$exists) {
                    $valuesToInsert[] = [
                        'product_id' => $productId,
                        'attribute_id' => $attributeId,
                        'product_attribute_id' => $productAttributeId,
                        'attribute_option_id' => $option->id,
                        'extra_price' => $option->extra_price,
                    ];
                }
            }

            if (!empty($valuesToInsert)) {
                DB::table('products_product_attribute_values')->insert($valuesToInsert);
            }
        }

        echo "\n✓ Successfully created Millwork Countertop product\n";
        echo "✓ UOM: Square Foot\n";
        echo "✓ Attributes: " . count($attributes) . " (Material, Finish, Edge Profile)\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $productId = DB::table('products_products')
            ->where('reference', 'COUNTERTOP')
            ->value('id');

        if ($productId) {
            // Delete attribute values
            DB::table('products_product_attribute_values')
                ->where('product_id', $productId)
                ->delete();

            // Delete attribute mappings
            DB::table('products_product_attributes')
                ->where('product_id', $productId)
                ->delete();

            // Delete product
            DB::table('products_products')
                ->where('id', $productId)
                ->delete();

            echo "Removed Millwork Countertop product\n";
        }
    }
};
