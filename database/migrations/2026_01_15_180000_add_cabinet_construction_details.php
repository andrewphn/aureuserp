<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Cabinet Construction Details
 *
 * Based on TCS shop practices from Bryan Patton (Jan 2025):
 * - Top construction type (stretchers vs full top)
 * - Stretcher height (TCS standard: 3")
 * - Sink cabinet side extension (3/4" for countertop support)
 * - Face frame door gap (1/8" standard)
 * - Material product links (box, face frame, edge banding)
 * - Fixed dividers table (smell isolation, section division)
 *
 * @see docs/DATABASE_HIERARCHY.md
 * @see app/Services/CabinetConfiguratorService.php
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects_cabinets', function (Blueprint $table) {
            // Top construction type
            // Bryan: "Lower cabinet would have 3 inch stretchers because it doesn't need a top"
            if (!Schema::hasColumn('projects_cabinets', 'top_construction_type')) {
                $table->string('top_construction_type', 20)
                    ->default('stretchers')
                    ->after('construction_type')
                    ->comment('Top: stretchers (base), full_top (wall), none');
            }

            // Stretcher height (TCS standard: 3")
            // Bryan: "3 inch stretchers"
            if (!Schema::hasColumn('projects_cabinets', 'stretcher_height_inches')) {
                $table->decimal('stretcher_height_inches', 5, 3)
                    ->default(3.0)
                    ->after('top_construction_type')
                    ->comment('Stretcher height in inches (TCS: 3")');
            }

            // Sink cabinet side extension
            // Bryan: "At sink locations... sides will come up an additional 3/4 of an inch"
            if (!Schema::hasColumn('projects_cabinets', 'sink_requires_extended_sides')) {
                $table->boolean('sink_requires_extended_sides')
                    ->default(false)
                    ->after('stretcher_height_inches')
                    ->comment('Sink cabinet sides extend for countertop');
            }

            if (!Schema::hasColumn('projects_cabinets', 'sink_side_extension_inches')) {
                $table->decimal('sink_side_extension_inches', 5, 3)
                    ->default(0.75)
                    ->after('sink_requires_extended_sides')
                    ->comment('Extension height (standard: 3/4")');
            }

            // Face frame door gap
            // Bryan: "1.5" or 1 3/4" stile, then you have an 8th inch gap to your door"
            if (!Schema::hasColumn('projects_cabinets', 'face_frame_door_gap_inches')) {
                $table->decimal('face_frame_door_gap_inches', 5, 3)
                    ->default(0.125)
                    ->after('face_frame_mid_stile_count')
                    ->comment('Gap between face frame and door (1/8")');
            }

            // Material product links - connect to actual inventory products
            if (!Schema::hasColumn('projects_cabinets', 'box_material_product_id')) {
                $table->unsignedBigInteger('box_material_product_id')
                    ->nullable()
                    ->after('product_id')
                    ->comment('FK to sheet goods product for box');

                $table->foreign('box_material_product_id', 'fk_cabinet_box_material')
                    ->references('id')
                    ->on('products_products')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('projects_cabinets', 'face_frame_material_product_id')) {
                $table->unsignedBigInteger('face_frame_material_product_id')
                    ->nullable()
                    ->after('box_material_product_id')
                    ->comment('FK to lumber product for face frame');

                $table->foreign('face_frame_material_product_id', 'fk_cabinet_face_frame_material')
                    ->references('id')
                    ->on('products_products')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('projects_cabinets', 'edge_banding_product_id')) {
                $table->unsignedBigInteger('edge_banding_product_id')
                    ->nullable()
                    ->after('face_frame_material_product_id')
                    ->comment('FK to edge banding product');

                $table->foreign('edge_banding_product_id', 'fk_cabinet_edge_banding')
                    ->references('id')
                    ->on('products_products')
                    ->nullOnDelete();
            }
        });

        // Create fixed dividers table
        // Bryan: "A full depth fixed divider if it needed a division between
        // a drawer section and a hanging section... or if it was a trash
        // cabinet and you didn't want the smells to come through"
        if (!Schema::hasTable('projects_fixed_dividers')) {
            Schema::create('projects_fixed_dividers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cabinet_id');
                $table->decimal('position_from_left_inches', 8, 4)->nullable()
                    ->comment('Horizontal position from left side of cabinet');
                $table->decimal('position_from_bottom_inches', 8, 4)->nullable()
                    ->comment('Vertical position from bottom (for horizontal dividers)');
                $table->string('orientation', 20)->default('vertical')
                    ->comment('vertical or horizontal');
                $table->string('purpose', 50)->default('section_division')
                    ->comment('section_division, smell_isolation, structural');
                $table->decimal('width_inches', 5, 3)->nullable()
                    ->comment('Divider width (for vertical) or length (for horizontal)');
                $table->decimal('height_inches', 5, 3)->nullable()
                    ->comment('Divider height');
                $table->decimal('depth_inches', 5, 3)->nullable()
                    ->comment('Full depth or partial depth');
                $table->decimal('thickness_inches', 5, 3)->default(0.75)
                    ->comment('Material thickness (TCS: 3/4")');
                $table->string('material', 100)->default('3/4 maple plywood')
                    ->comment('Material description');
                $table->unsignedBigInteger('material_product_id')->nullable()
                    ->comment('FK to products for material tracking');
                $table->text('notes')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->cascadeOnDelete();

                $table->foreign('material_product_id')
                    ->references('id')
                    ->on('products_products')
                    ->nullOnDelete();

                $table->index('cabinet_id');
                $table->index('purpose');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_fixed_dividers');

        Schema::table('projects_cabinets', function (Blueprint $table) {
            // Drop foreign keys first
            $foreignKeys = [
                'fk_cabinet_box_material',
                'fk_cabinet_face_frame_material',
                'fk_cabinet_edge_banding',
            ];

            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk);
                } catch (\Exception $e) {
                    // Foreign key may not exist
                }
            }

            // Drop columns
            $columns = [
                'top_construction_type',
                'stretcher_height_inches',
                'sink_requires_extended_sides',
                'sink_side_extension_inches',
                'face_frame_door_gap_inches',
                'box_material_product_id',
                'face_frame_material_product_id',
                'edge_banding_product_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('projects_cabinets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
