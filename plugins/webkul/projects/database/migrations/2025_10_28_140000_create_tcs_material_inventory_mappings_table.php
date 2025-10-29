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
     * Creates mapping between TCS pricing material categories and actual inventory products.
     *
     * TCS Material Categories (from products_attribute_options):
     * - Paint Grade (Hard Maple/Poplar) - +$138/LF
     * - Stain Grade (Oak/Maple) - +$156/LF
     * - Premium (Rifted White Oak/Black Walnut) - +$185/LF
     * - Custom/Exotic - Price TBD
     *
     * Maps to actual inventory products for BOM generation.
     */
    public function up(): void
    {
        Schema::create('tcs_material_inventory_mappings', function (Blueprint $table) {
            $table->id();

            // TCS pricing material category (slug from attribute option)
            $table->string('tcs_material_slug', 50)->index();

            // Specific wood species within that category
            $table->string('wood_species', 100);

            // Inventory product reference (for actual material SKU)
            $table->foreignId('inventory_product_id')
                ->nullable()
                ->constrained('products_products')
                ->onDelete('set null');

            // Woodworking material category (Sheet Goods, Solid Wood, etc.)
            $table->foreignId('material_category_id')
                ->nullable()
                ->constrained('woodworking_material_categories')
                ->onDelete('set null');

            // Material usage multipliers per linear foot
            $table->decimal('board_feet_per_lf', 8, 4)->default(0); // For solid wood
            $table->decimal('sheet_sqft_per_lf', 8, 4)->default(0); // For sheet goods

            // Material type flags
            $table->boolean('is_box_material')->default(false);    // Used for cabinet boxes
            $table->boolean('is_face_frame_material')->default(false); // Used for face frames
            $table->boolean('is_door_material')->default(false);   // Used for doors/drawers

            // Priority for material selection (lower = preferred)
            $table->integer('priority')->default(100);

            // Availability
            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint: each species within a TCS category
            $table->unique(['tcs_material_slug', 'wood_species'], 'tcs_material_species_unique');
        });

        // Seed initial mappings based on TCS price sheets
        $this->seedMaterialMappings();
    }

    /**
     * Seed initial TCS material to inventory mappings
     */
    protected function seedMaterialMappings(): void
    {
        $now = now();

        // Paint Grade Materials (+$138/LF)
        $paintGradeMaterials = [
            [
                'tcs_material_slug' => 'paint_grade',
                'wood_species' => 'Hard Maple',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 2.5,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 10,
            ],
            [
                'tcs_material_slug' => 'paint_grade',
                'wood_species' => 'Poplar',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 2.5,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 20,
            ],
            [
                'tcs_material_slug' => 'paint_grade',
                'wood_species' => 'Birch Plywood',
                'material_category_id' => 1, // Sheet Goods - Plywood
                'board_feet_per_lf' => 0,
                'sheet_sqft_per_lf' => 6.0,
                'is_box_material' => true,
                'is_face_frame_material' => false,
                'is_door_material' => false,
                'priority' => 15,
            ],
        ];

        // Stain Grade Materials (+$156/LF)
        $stainGradeMaterials = [
            [
                'tcs_material_slug' => 'stain_grade',
                'wood_species' => 'Red Oak',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 2.5,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 10,
            ],
            [
                'tcs_material_slug' => 'stain_grade',
                'wood_species' => 'White Oak',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 2.5,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 15,
            ],
            [
                'tcs_material_slug' => 'stain_grade',
                'wood_species' => 'Hard Maple (Stain)',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 2.5,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 20,
            ],
        ];

        // Premium Materials (+$185/LF)
        $premiumMaterials = [
            [
                'tcs_material_slug' => 'premium',
                'wood_species' => 'Rifted White Oak',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 2.8,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 10,
            ],
            [
                'tcs_material_slug' => 'premium',
                'wood_species' => 'Black Walnut',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 2.8,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 15,
            ],
            [
                'tcs_material_slug' => 'premium',
                'wood_species' => 'Cherry',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 2.7,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 20,
            ],
        ];

        // Custom/Exotic Materials (Price TBD)
        $customMaterials = [
            [
                'tcs_material_slug' => 'custom_exotic',
                'wood_species' => 'Exotic/Custom Wood',
                'material_category_id' => 3, // Solid Wood - Hardwoods
                'board_feet_per_lf' => 3.0,
                'sheet_sqft_per_lf' => 0,
                'is_box_material' => true,
                'is_face_frame_material' => true,
                'is_door_material' => true,
                'priority' => 10,
                'notes' => 'Pricing determined per project - includes specialty woods like mahogany, teak, etc.',
            ],
        ];

        $allMaterials = array_merge(
            $paintGradeMaterials,
            $stainGradeMaterials,
            $premiumMaterials,
            $customMaterials
        );

        foreach ($allMaterials as $material) {
            $material['created_at'] = $now;
            $material['updated_at'] = $now;
            DB::table('tcs_material_inventory_mappings')->insert($material);
        }

        echo "✓ Seeded " . count($allMaterials) . " TCS material to inventory mappings\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcs_material_inventory_mappings');
    }
};
