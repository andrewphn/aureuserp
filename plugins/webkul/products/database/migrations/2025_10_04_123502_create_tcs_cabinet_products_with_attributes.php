<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates TCS Cabinet products and maps all attributes to them.
     * Based on cabinet-product-attributes-mapping.md specifications.
     */
    public function up(): void
    {
        $now = now();

        // Get or create category
        $categoryId = DB::table('products_categories')->where('name', 'Woodwork Services')->value('id');
        if (!$categoryId) {
            DB::table('products_categories')->insert([
                'name' => 'Woodwork Services',
                'full_name' => 'Woodwork Services',
                'creator_id' => DB::table('users')->value('id'), // Use first available user or NULL
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryId = DB::getPdo()->lastInsertId();
        }

        // Get TCS company
        $companyId = DB::table('companies')->where('acronym', 'TCS')->value('id')
            ?? DB::table('companies')->where('name', 'The Carpenter\'s Son')->value('id')
            ?? 1;

        // Get all attribute IDs (including Cabinet Type)
        $attributes = DB::table('products_attributes')
            ->whereIn('name', [
                'Cabinet Type',
                'Construction Style',
                'Door Style',
                'Primary Material',
                'Finish Type',
                'Edge Profile',
                'Drawer Box Construction',
                'Door Overlay Type',
                'Box Material'
            ])
            ->pluck('id', 'name')
            ->toArray();

        // Get or create Linear Foot UOM
        $uomId = DB::table('unit_of_measures')->where('name', 'Linear Foot')->value('id');
        if (!$uomId) {
            $categoryUomId = DB::table('unit_of_measure_categories')->where('name', 'Length')->value('id');
            if (!$categoryUomId) {
                DB::table('unit_of_measure_categories')->insert([
                    'name' => 'Length',
                    'creator_id' => DB::table('users')->value('id'), // Use first available user or NULL
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $categoryUomId = DB::getPdo()->lastInsertId();
            }

            DB::table('unit_of_measures')->insert([
                'name' => 'Linear Foot',
                'category_id' => $categoryUomId,
                'type' => 'bigger',
                'factor' => 1,
                'rounding' => 0.01,
                'creator_id' => DB::table('users')->value('id'), // Use first available user or NULL
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $uomId = DB::getPdo()->lastInsertId();
        }

        // SINGLE CABINET PRODUCT - variants created from attribute combinations
        $cabinetProduct = [
            'name' => 'Cabinet',
            'description' => 'Custom cabinet - configure type, style, material, and finish options to create your perfect cabinet.',
            'type' => 'service',
            'price' => 168.00, // Base Level 2 price
            'cost' => 0,
            'reference' => 'CABINET',
            'category_id' => $categoryId,
            'company_id' => $companyId,
            'uom_id' => $uomId,
            'uom_po_id' => $uomId,
            'creator_id' => DB::table('users')->value('id'), // Use first available user or NULL
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Check if product already exists
        $existingId = DB::table('products_products')
            ->where('reference', 'CABINET')
            ->value('id');

        if ($existingId) {
            echo "Product already exists: Cabinet (ID: {$existingId})\n";
            $productId = $existingId;
        } else {
            DB::table('products_products')->insert($cabinetProduct);
            $productId = DB::getPdo()->lastInsertId();
            echo "Created product: Cabinet (ID: {$productId})\n";
        }

        // Map all 9 attributes to Cabinet product
        $attributeMappings = [];
        $sort = 1;
        foreach ($attributes as $attributeName => $attributeId) {
            // Check if mapping already exists
            $exists = DB::table('products_product_attributes')
                ->where('product_id', $productId)
                ->where('attribute_id', $attributeId)
                ->exists();

            if (!$exists) {
                $attributeMappings[] = [
                    'sort' => $sort++,
                    'product_id' => $productId,
                    'attribute_id' => $attributeId,
                    'creator_id' => DB::table('users')->value('id'), // Use first available user or NULL
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($attributeMappings)) {
            DB::table('products_product_attributes')->insert($attributeMappings);
            echo "  → Mapped " . count($attributeMappings) . " attributes\n";
        } else {
            echo "  → Attributes already mapped\n";
        }

        echo "\n✓ Successfully created Cabinet product with " . count($attributes) . " attribute mappings\n";
        echo "✓ Variants can be generated from: Cabinet Type × Construction Style × Door Style × Materials\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get Cabinet product ID
        $productId = DB::table('products_products')
            ->where('reference', 'CABINET')
            ->value('id');

        if ($productId) {
            // Delete attribute mappings
            DB::table('products_product_attributes')
                ->where('product_id', $productId)
                ->delete();

            // Delete product
            DB::table('products_products')
                ->where('id', $productId)
                ->delete();

            echo "Removed Cabinet product and attribute mappings\n";
        }
    }
};
