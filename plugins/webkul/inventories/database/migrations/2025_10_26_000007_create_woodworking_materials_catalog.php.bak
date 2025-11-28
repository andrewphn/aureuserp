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
     * Woodworking materials catalog - extends products table with woodworking-specific fields
     * Seeds standard TCS materials from pricing sheets
     */
    public function up(): void
    {
        // Create material categories table (skip if already exists)
        if (!Schema::hasTable('woodworking_material_categories')) {
            Schema::create('woodworking_material_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 50)->unique();
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Add woodworking-specific columns to products table
        Schema::table('products_products', function (Blueprint $table) {
            // Material Classification
            if (!Schema::hasColumn('products_products', 'material_category_id')) {
                $table->foreignId('material_category_id')->nullable()
                    ->comment('Link to woodworking_material_categories');
                $table->foreign('material_category_id')
                    ->references('id')
                    ->on('woodworking_material_categories')
                    ->onDelete('set null');
            }

            if (!Schema::hasColumn('products_products', 'material_type')) {
                $table->string('material_type', 50)->nullable()
                    ->comment('sheet_goods, solid_wood, hardware, finish, accessory');
            }
            if (!Schema::hasColumn('products_products', 'wood_species')) {
                $table->string('wood_species', 100)->nullable()
                    ->comment('hard_maple, white_oak, walnut, etc.');
            }
            if (!Schema::hasColumn('products_products', 'grade')) {
                $table->string('grade', 50)->nullable()
                    ->comment('Material grade: select, #1, #2, paint_grade, etc.');
            }

            // Dimensional Properties
            if (!Schema::hasColumn('products_products', 'thickness_inches')) {
                $table->decimal('thickness_inches', 5, 3)->nullable()
                    ->comment('Standard thickness (0.75 for 3/4", 0.5 for 1/2")');
            }
            if (!Schema::hasColumn('products_products', 'width_inches')) {
                $table->decimal('width_inches', 8, 3)->nullable()
                    ->comment('Standard width (for lumber/boards)');
            }
            if (!Schema::hasColumn('products_products', 'length_inches')) {
                $table->decimal('length_inches', 8, 3)->nullable()
                    ->comment('Standard length');
            }
            if (!Schema::hasColumn('products_products', 'sheet_size')) {
                $table->string('sheet_size', 50)->nullable()
                    ->comment('Sheet dimensions: 4x8, 4x10, etc.');
            }

            // Unit of Measure for Woodworking
            if (!Schema::hasColumn('products_products', 'woodworking_uom')) {
                $table->string('woodworking_uom', 50)->nullable()
                    ->comment('BF (board feet), SQFT, EA, LF, etc.');
            }
            if (!Schema::hasColumn('products_products', 'sqft_per_sheet')) {
                $table->decimal('sqft_per_sheet', 8, 2)->nullable()
                    ->comment('Square feet per sheet (32 for 4x8)');
            }
            if (!Schema::hasColumn('products_products', 'bf_per_unit')) {
                $table->decimal('bf_per_unit', 8, 2)->nullable()
                    ->comment('Board feet per unit for solid wood');
            }
            if (!Schema::hasColumn('products_products', 'lf_per_unit')) {
                $table->decimal('lf_per_unit', 8, 2)->nullable()
                    ->comment('Linear feet per unit');
            }

            // Pricing
            if (!Schema::hasColumn('products_products', 'cost_per_unit')) {
                $table->decimal('cost_per_unit', 10, 2)->nullable()
                    ->comment('Current cost per woodworking_uom');
            }
            if (!Schema::hasColumn('products_products', 'price_per_lf_upgrade')) {
                $table->decimal('price_per_lf_upgrade', 8, 2)->nullable()
                    ->comment('Price $/LF when used as material upgrade (from price sheet)');
            }

            // Material Properties
            if (!Schema::hasColumn('products_products', 'suitable_for_paint')) {
                $table->boolean('suitable_for_paint')->default(true)
                    ->comment('Good for paint grade');
            }
            if (!Schema::hasColumn('products_products', 'suitable_for_stain')) {
                $table->boolean('suitable_for_stain')->default(true)
                    ->comment('Good for stain grade');
            }
            if (!Schema::hasColumn('products_products', 'core_type')) {
                $table->string('core_type', 50)->nullable()
                    ->comment('Plywood core: veneer, mdf, particle_board');
            }
            if (!Schema::hasColumn('products_products', 'material_notes')) {
                $table->text('material_notes')->nullable()
                    ->comment('Special properties, uses, considerations');
            }

            // Inventory Minimum (for material ordering)
            if (!Schema::hasColumn('products_products', 'minimum_stock_level')) {
                $table->decimal('minimum_stock_level', 10, 2)->nullable()
                    ->comment('Reorder when below this quantity');
            }
            if (!Schema::hasColumn('products_products', 'reorder_quantity')) {
                $table->decimal('reorder_quantity', 10, 2)->nullable()
                    ->comment('Standard reorder quantity');
            }

            // Note: Index creation skipped - add manually if needed
            // Schema facade doesn't support checking for existing indexes in Laravel 11
        });

        // Seed material categories
        $categories = [
            ['name' => 'Sheet Goods - Plywood', 'code' => 'PLYWOOD', 'description' => 'Cabinet-grade plywood for boxes and panels'],
            ['name' => 'Sheet Goods - MDF', 'code' => 'MDF', 'description' => 'Medium density fiberboard for painted applications'],
            ['name' => 'Solid Wood - Hardwoods', 'code' => 'HARDWOOD', 'description' => 'Solid hardwood lumber for face frames and doors'],
            ['name' => 'Solid Wood - Softwoods', 'code' => 'SOFTWOOD', 'description' => 'Softwood lumber for secondary components'],
            ['name' => 'Edge Banding', 'code' => 'EDGE_BAND', 'description' => 'Edge banding and veneer tape'],
            ['name' => 'Hardware - Hinges', 'code' => 'HINGES', 'description' => 'Cabinet door hinges (Blum, etc.)'],
            ['name' => 'Hardware - Slides', 'code' => 'SLIDES', 'description' => 'Drawer slides and undermounts'],
            ['name' => 'Hardware - Shelf Pins', 'code' => 'SHELF_PINS', 'description' => 'Adjustable shelf support pins'],
            ['name' => 'Accessories - Rev-a-Shelf', 'code' => 'REVASHELF', 'description' => 'Rev-a-Shelf pullouts and organizers'],
            ['name' => 'Finishes - Paint', 'code' => 'PAINT', 'description' => 'Paint and primer products'],
            ['name' => 'Finishes - Stain', 'code' => 'STAIN', 'description' => 'Stain and topcoat products'],
        ];

        foreach ($categories as $index => $category) {
            // Only insert if category doesn't already exist
            $exists = DB::table('woodworking_material_categories')
                ->where('code', $category['code'])
                ->exists();

            if (!$exists) {
                DB::table('woodworking_material_categories')->insert([
                    'name' => $category['name'],
                    'code' => $category['code'],
                    'description' => $category['description'],
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Seed standard materials from TCS price sheet (only if no materials exist yet)
        $materialCount = DB::table('products_products')
            ->whereNotNull('material_category_id')
            ->count();

        if ($materialCount == 0) {
            $this->seedStandardMaterials();
        }
    }

    /**
     * Seed standard materials based on TCS price sheet
     */
    protected function seedStandardMaterials(): void
    {
        $plywoodCategoryId = DB::table('woodworking_material_categories')->where('code', 'PLYWOOD')->value('id');
        $hardwoodCategoryId = DB::table('woodworking_material_categories')->where('code', 'HARDWOOD')->value('id');

        // Map woodworking UOM codes to database IDs
        $uomMap = [
            'SHEET' => 1,  // Units
            'BF' => 1,     // Board Feet (using Units as reference)
            'LF' => 27,    // Linear Foot
            'SQFT' => 28,  // Square Foot
        ];

        $materials = [
            // Sheet Goods - Plywood
            [
                'name' => '3/4" Birch Plywood 4x8',
                'material_category_id' => $plywoodCategoryId,
                'material_type' => 'sheet_goods',
                'wood_species' => 'birch',
                'grade' => 'cabinet_grade',
                'thickness_inches' => 0.75,
                'sheet_size' => '4x8',
                'sqft_per_sheet' => 32,
                'woodworking_uom' => 'SHEET',
                'suitable_for_paint' => true,
                'suitable_for_stain' => true,
                'core_type' => 'veneer',
                'cost_per_unit' => 85.00,
            ],
            [
                'name' => '3/4" Maple Plywood 4x8 (Paint Grade)',
                'material_category_id' => $plywoodCategoryId,
                'material_type' => 'sheet_goods',
                'wood_species' => 'hard_maple',
                'grade' => 'paint_grade',
                'thickness_inches' => 0.75,
                'sheet_size' => '4x8',
                'sqft_per_sheet' => 32,
                'woodworking_uom' => 'SHEET',
                'suitable_for_paint' => true,
                'suitable_for_stain' => false,
                'core_type' => 'veneer',
                'cost_per_unit' => 90.00,
                'price_per_lf_upgrade' => 138.00, // From price sheet
            ],
            [
                'name' => '3/4" White Oak Plywood 4x8 (Rifted)',
                'material_category_id' => $plywoodCategoryId,
                'material_type' => 'sheet_goods',
                'wood_species' => 'white_oak',
                'grade' => 'select',
                'thickness_inches' => 0.75,
                'sheet_size' => '4x8',
                'sqft_per_sheet' => 32,
                'woodworking_uom' => 'SHEET',
                'suitable_for_paint' => false,
                'suitable_for_stain' => true,
                'core_type' => 'veneer',
                'cost_per_unit' => 145.00,
                'price_per_lf_upgrade' => 185.00, // Premium from price sheet
            ],

            // Solid Wood - Hardwoods
            [
                'name' => 'Hard Maple Lumber S4S 4/4',
                'material_category_id' => $hardwoodCategoryId,
                'material_type' => 'solid_wood',
                'wood_species' => 'hard_maple',
                'grade' => 'select',
                'thickness_inches' => 0.75,
                'woodworking_uom' => 'BF',
                'suitable_for_paint' => true,
                'suitable_for_stain' => true,
                'cost_per_unit' => 8.50,
                'material_notes' => 'Face frame stock, door rails/stiles',
            ],
            [
                'name' => 'White Oak Lumber S4S 4/4 (Rifted)',
                'material_category_id' => $hardwoodCategoryId,
                'material_type' => 'solid_wood',
                'wood_species' => 'white_oak',
                'grade' => 'select',
                'thickness_inches' => 0.75,
                'woodworking_uom' => 'BF',
                'suitable_for_paint' => false,
                'suitable_for_stain' => true,
                'cost_per_unit' => 12.00,
                'material_notes' => 'Premium face frames, visible components',
            ],
            [
                'name' => 'Poplar Lumber S4S 4/4',
                'material_category_id' => $hardwoodCategoryId,
                'material_type' => 'solid_wood',
                'wood_species' => 'poplar',
                'grade' => 'paint_grade',
                'thickness_inches' => 0.75,
                'woodworking_uom' => 'BF',
                'suitable_for_paint' => true,
                'suitable_for_stain' => false,
                'cost_per_unit' => 5.50,
                'material_notes' => 'Paint grade face frames, secondary components',
            ],
        ];

        foreach ($materials as $material) {
            // Map woodworking_uom to uom_id
            $uomCode = $material['woodworking_uom'] ?? 'SHEET';
            $uomId = $uomMap[$uomCode] ?? 1; // Default to Units if not found

            // Get first available category_id
            $categoryId = DB::table('products_categories')->first()->id ?? 1;

            DB::table('products_products')->insert(array_merge($material, [
                'category_id' => $categoryId,
                'uom_id' => $uomId,
                'uom_po_id' => $uomId, // Use same UOM for purchase orders
                'tracking' => 'lots',
                'is_storable' => true,
                'creator_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_products', function (Blueprint $table) {
            $table->dropIndex('idx_material_type_species');
            $table->dropForeign(['material_category_id']);

            $table->dropColumn([
                'material_category_id',
                'material_type',
                'wood_species',
                'grade',
                'thickness_inches',
                'width_inches',
                'length_inches',
                'sheet_size',
                'woodworking_uom',
                'sqft_per_sheet',
                'bf_per_unit',
                'cost_per_unit',
                'price_per_lf_upgrade',
                'suitable_for_paint',
                'suitable_for_stain',
                'core_type',
                'material_notes',
                'minimum_stock_level',
                'reorder_quantity',
            ]);
        });

        Schema::dropIfExists('woodworking_material_categories');
    }
};
