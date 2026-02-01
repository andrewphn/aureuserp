<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates all TCS services from price sheets with their proper attributes
     */
    public function up(): void
    {
        // Skip if products tables don't exist yet (plugin not installed)
        if (!Schema::hasTable('products_categories') || !Schema::hasTable('products_products')) {
            return;
        }

        $now = now();

        // Get category
        $categoryId = DB::table('products_categories')->where('name', 'Woodwork Services')->value('id');
        if (!$categoryId) {
            DB::table('products_categories')->insert([
                'name' => 'Woodwork Services',
                'full_name' => 'Woodwork Services',
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryId = DB::getPdo()->lastInsertId();
        }

        // Get TCS company
        $companyId = DB::table('companies')->where('acronym', 'TCS')->value('id')
            ?? DB::table('companies')->where('name', 'The Carpenter\'s Son')->value('id')
            ?? 1;

        // Get Linear Foot UOM
        $uomId = DB::table('unit_of_measures')->where('name', 'Linear Foot')->value('id');

        // Get or create Square Foot UOM
        $sqftUomId = DB::table('unit_of_measures')->where('name', 'Square Foot')->value('id');
        if (!$sqftUomId) {
            $categoryUomId = DB::table('unit_of_measure_categories')->where('name', 'Area')->value('id');
            if (!$categoryUomId) {
                DB::table('unit_of_measure_categories')->insert([
                    'name' => 'Area',
                    'creator_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $categoryUomId = DB::getPdo()->lastInsertId();
            }

            DB::table('unit_of_measures')->insert([
                'name' => 'Square Foot',
                'category_id' => $categoryUomId,
                'type' => 'bigger',
                'factor' => 1,
                'rounding' => 0.01,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sqftUomId = DB::getPdo()->lastInsertId();
        }

        // Get or create Sheet UOM
        $sheetUomId = DB::table('unit_of_measures')->where('name', 'Sheet')->value('id');
        if (!$sheetUomId) {
            $categoryUomId = DB::table('unit_of_measure_categories')->where('name', 'Unit')->value('id');
            if (!$categoryUomId) {
                DB::table('unit_of_measure_categories')->insert([
                    'name' => 'Unit',
                    'creator_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $categoryUomId = DB::getPdo()->lastInsertId();
            }

            DB::table('unit_of_measures')->insert([
                'name' => 'Sheet',
                'category_id' => $categoryUomId,
                'type' => 'reference',
                'factor' => 1,
                'rounding' => 0.01,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sheetUomId = DB::getPdo()->lastInsertId();
        }

        // ==================== CREATE NEW ATTRIBUTES ====================

        // Material Grade attribute (for floating shelves, trim)
        $materialGradeAttrId = DB::table('products_attributes')->where('name', 'Material Grade')->value('id');
        if (!$materialGradeAttrId) {
            DB::table('products_attributes')->insert([
                'name' => 'Material Grade',
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $materialGradeAttrId = DB::getPdo()->lastInsertId();

            // Add options
            $gradeOptions = [
                ['name' => 'Paint Grade', 'extra_price' => 0],
                ['name' => 'Stain Grade', 'extra_price' => 0],
                ['name' => 'Premium', 'extra_price' => 6.00],
            ];

            foreach ($gradeOptions as $option) {
                DB::table('products_attribute_options')->insert([
                    'attribute_id' => $materialGradeAttrId,
                    'name' => $option['name'],
                    'extra_price' => $option['extra_price'],
                    'creator_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Trim Level attribute (for baseboards, crown, etc.)
        $trimLevelAttrId = DB::table('products_attributes')->where('name', 'Trim Level')->value('id');
        if (!$trimLevelAttrId) {
            DB::table('products_attributes')->insert([
                'name' => 'Trim Level',
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $trimLevelAttrId = DB::getPdo()->lastInsertId();

            // Add options
            $levelOptions = [
                ['name' => 'Level 1', 'extra_price' => 0],
                ['name' => 'Level 2', 'extra_price' => 2.00],
            ];

            foreach ($levelOptions as $option) {
                DB::table('products_attribute_options')->insert([
                    'attribute_id' => $trimLevelAttrId,
                    'name' => $option['name'],
                    'extra_price' => $option['extra_price'],
                    'creator_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Finish Type attribute (for finish services)
        $finishTypeAttrId = DB::table('products_attributes')->where('name', 'Finish Type')->value('id');

        // Panel Type attribute (for wall paneling)
        $panelTypeAttrId = DB::table('products_attributes')->where('name', 'Panel Type')->value('id');
        if (!$panelTypeAttrId) {
            DB::table('products_attributes')->insert([
                'name' => 'Panel Type',
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $panelTypeAttrId = DB::getPdo()->lastInsertId();

            // Add options
            $panelOptions = [
                ['name' => 'CNC Cut', 'extra_price' => 0],
                ['name' => 'Beadboard', 'extra_price' => 23.49],
            ];

            foreach ($panelOptions as $option) {
                DB::table('products_attribute_options')->insert([
                    'attribute_id' => $panelTypeAttrId,
                    'name' => $option['name'],
                    'extra_price' => $option['extra_price'],
                    'creator_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Panel Thickness attribute
        $panelThicknessAttrId = DB::table('products_attributes')->where('name', 'Panel Thickness')->value('id');
        if (!$panelThicknessAttrId) {
            DB::table('products_attributes')->insert([
                'name' => 'Panel Thickness',
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $panelThicknessAttrId = DB::getPdo()->lastInsertId();

            // Add options
            $thicknessOptions = [
                ['name' => '1/2"', 'extra_price' => 0],
                ['name' => '3/4"', 'extra_price' => 24.99],
            ];

            foreach ($thicknessOptions as $option) {
                DB::table('products_attribute_options')->insert([
                    'attribute_id' => $panelThicknessAttrId,
                    'name' => $option['name'],
                    'extra_price' => $option['extra_price'],
                    'creator_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // ==================== CREATE PRODUCTS ====================

        $products = [
            // 1. Closet Shelf & Rod
            [
                'name' => 'Closet Shelf & Rod',
                'description' => 'Paint Grade only - Wood only, no hardware',
                'type' => 'service',
                'price' => 28.00,
                'reference' => 'CLOSET-SHELF-ROD',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 2. Floating Shelf
            [
                'name' => 'Floating Shelf',
                'description' => 'Standard 1.75" thick × 10" deep - Wood only, no hardware',
                'type' => 'service',
                'price' => 18.00,
                'reference' => 'FLOATING-SHELF',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 3. Window Sill L1
            [
                'name' => 'Window Sill L1',
                'description' => 'Standard 5/4" x 4-6" flat',
                'type' => 'service',
                'price' => 8.00,
                'reference' => 'SILL-L1',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 4. Window Sill L2
            [
                'name' => 'Window Sill L2',
                'description' => 'Rabbeted edge, standard profile',
                'type' => 'service',
                'price' => 12.00,
                'reference' => 'SILL-L2',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 5. Baseboard
            [
                'name' => 'Baseboard',
                'description' => 'Manufacturing only - Simple beaded or standard profiles',
                'type' => 'service',
                'price' => 4.00,
                'reference' => 'BASEBOARD',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 6. Chair Rail
            [
                'name' => 'Chair Rail',
                'description' => 'Manufacturing only - Simple beaded',
                'type' => 'service',
                'price' => 4.00,
                'reference' => 'CHAIR-RAIL',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 7. Casing
            [
                'name' => 'Casing',
                'description' => 'Manufacturing only - Simple flat stock',
                'type' => 'service',
                'price' => 6.00,
                'reference' => 'CASING',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 8. Crown Molding
            [
                'name' => 'Crown Molding',
                'description' => 'Manufacturing only - Simple cove 3.5-5.5"',
                'type' => 'service',
                'price' => 6.00,
                'reference' => 'CROWN',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 9. Wall Paneling
            [
                'name' => 'Wall Paneling',
                'description' => 'Manufacturing only - Medex panels (4\' x 8\' sheets)',
                'type' => 'service',
                'price' => 58.00,
                'reference' => 'WALL-PANEL',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $sheetUomId,
                'uom_po_id' => $sheetUomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 10-17. Finish Services
            [
                'name' => 'Unfinished',
                'description' => 'Ready for your finishing',
                'type' => 'service',
                'price' => 0.00,
                'reference' => 'FINISH-UNFINISHED',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Prime Only',
                'description' => 'Primer ready for paint',
                'type' => 'service',
                'price' => 60.00,
                'reference' => 'FINISH-PRIME',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Prime + Paint',
                'description' => 'Complete paint finish',
                'type' => 'service',
                'price' => 118.00,
                'reference' => 'FINISH-PRIME-PAINT',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Custom Color',
                'description' => 'Custom color matching',
                'type' => 'service',
                'price' => 125.00,
                'reference' => 'FINISH-CUSTOM-COLOR',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Clear Coat',
                'description' => 'Clear protective finish',
                'type' => 'service',
                'price' => 95.00,
                'reference' => 'FINISH-CLEAR',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Stain + Clear',
                'description' => 'Natural wood stain + clear coat',
                'type' => 'service',
                'price' => 213.00,
                'reference' => 'FINISH-STAIN-CLEAR',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Color Match Stain + Clear',
                'description' => 'Custom color match stain + clear coat',
                'type' => 'service',
                'price' => 255.00,
                'reference' => 'FINISH-COLOR-MATCH',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Two-tone',
                'description' => 'Multiple color finish',
                'type' => 'service',
                'price' => 235.00,
                'reference' => 'FINISH-TWO-TONE',
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'creator_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert products and map attributes
        foreach ($products as $productData) {
            // Check if product exists
            $existingId = DB::table('products_products')
                ->where('reference', $productData['reference'])
                ->value('id');

            if ($existingId) {
                echo "Product already exists: {$productData['name']} (ID: {$existingId})\n";
                continue;
            }

            // Insert product
            DB::table('products_products')->insert($productData);
            $productId = DB::getPdo()->lastInsertId();
            echo "Created product: {$productData['name']} (ID: {$productId})\n";

            // Map attributes based on product type
            $attributeMappings = [];
            $sort = 1;

            switch ($productData['reference']) {
                case 'FLOATING-SHELF':
                    // Material Grade
                    $attributeMappings[] = [
                        'sort' => $sort++,
                        'product_id' => $productId,
                        'attribute_id' => $materialGradeAttrId,
                        'creator_id' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    break;

                case 'SILL-L1':
                case 'SILL-L2':
                case 'BASEBOARD':
                case 'CHAIR-RAIL':
                case 'CASING':
                    // Material Grade
                    $attributeMappings[] = [
                        'sort' => $sort++,
                        'product_id' => $productId,
                        'attribute_id' => $materialGradeAttrId,
                        'creator_id' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    break;

                case 'CROWN':
                    // Trim Level and Material Grade
                    $attributeMappings[] = [
                        'sort' => $sort++,
                        'product_id' => $productId,
                        'attribute_id' => $trimLevelAttrId,
                        'creator_id' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $attributeMappings[] = [
                        'sort' => $sort++,
                        'product_id' => $productId,
                        'attribute_id' => $materialGradeAttrId,
                        'creator_id' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    break;

                case 'WALL-PANEL':
                    // Panel Type and Panel Thickness
                    $attributeMappings[] = [
                        'sort' => $sort++,
                        'product_id' => $productId,
                        'attribute_id' => $panelTypeAttrId,
                        'creator_id' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $attributeMappings[] = [
                        'sort' => $sort++,
                        'product_id' => $productId,
                        'attribute_id' => $panelThicknessAttrId,
                        'creator_id' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    break;
            }

            if (!empty($attributeMappings)) {
                DB::table('products_product_attributes')->insert($attributeMappings);
                echo "  → Mapped " . count($attributeMappings) . " attributes\n";

                // Populate attribute values
                foreach ($attributeMappings as $mapping) {
                    $options = DB::table('products_attribute_options')
                        ->where('attribute_id', $mapping['attribute_id'])
                        ->get();

                    foreach ($options as $option) {
                        DB::table('products_product_attribute_values')->insert([
                            'product_id' => $productId,
                            'attribute_id' => $mapping['attribute_id'],
                            'product_attribute_id' => DB::table('products_product_attributes')
                                ->where('product_id', $productId)
                                ->where('attribute_id', $mapping['attribute_id'])
                                ->value('id'),
                            'attribute_option_id' => $option->id,
                            'extra_price' => $option->extra_price ?? 0,
                        ]);
                    }
                }
                echo "  → Populated attribute values\n";
            }
        }

        echo "\n✓ Successfully created all TCS services from price sheets\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products_products')) {
            return;
        }

        $references = [
            'CLOSET-SHELF-ROD',
            'FLOATING-SHELF',
            'SILL-L1',
            'SILL-L2',
            'BASEBOARD',
            'CHAIR-RAIL',
            'CASING',
            'CROWN',
            'WALL-PANEL',
            'FINISH-UNFINISHED',
            'FINISH-PRIME',
            'FINISH-PRIME-PAINT',
            'FINISH-CUSTOM-COLOR',
            'FINISH-CLEAR',
            'FINISH-STAIN-CLEAR',
            'FINISH-COLOR-MATCH',
            'FINISH-TWO-TONE',
        ];

        foreach ($references as $reference) {
            $productId = DB::table('products_products')
                ->where('reference', $reference)
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
            }
        }
    }
};
