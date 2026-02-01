<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add shelf specification fields for adjustable shelf calculations.
 * 
 * Shop Practice (from carpenter interview):
 * - Pin holes: 5mm diameter, 2" from front edge, 2" from back edge
 * - Vertical spacing: 2" between holes (4" total adjustment range)
 * - Center support: Add 3rd column of pins at 28"+ width
 * - Notch depth: 3/8" (standard) or 5/8" (deep) depending on hardware
 * - Edge banding: Front edge ONLY
 * - Minimum opening height: 5.5" (practical), 3/4" (absolute)
 */
return new class extends Migration
{
    public function up(): void
    {
        
        if (!Schema::hasTable('products_products')) {
            return;
        }

Schema::table('projects_shelves', function (Blueprint $table) {
            // ===== OPENING REFERENCE (input dimensions) =====
            $table->decimal('opening_width_inches', 8, 4)->nullable()
                ->after('thickness_inches')
                ->comment('Cabinet opening width - source dimension');
            $table->decimal('opening_height_inches', 8, 4)->nullable()
                ->after('opening_width_inches')
                ->comment('Cabinet opening height - source dimension');
            $table->decimal('opening_depth_inches', 8, 4)->nullable()
                ->after('opening_height_inches')
                ->comment('Cabinet opening depth - source dimension');

            // ===== CALCULATED DIMENSIONS =====
            $table->decimal('cut_width_inches', 8, 4)->nullable()
                ->after('opening_depth_inches')
                ->comment('Final cut width (opening - side clearances)');
            $table->decimal('cut_depth_inches', 8, 4)->nullable()
                ->after('cut_width_inches')
                ->comment('Final cut depth (opening - back clearance)');

            // ===== PIN HOLE SPECIFICATIONS =====
            $table->decimal('pin_setback_front_inches', 8, 4)->nullable()
                ->after('number_of_positions')
                ->comment('Distance from front edge to pin hole column (shop: 2")');
            $table->decimal('pin_setback_back_inches', 8, 4)->nullable()
                ->after('pin_setback_front_inches')
                ->comment('Distance from back edge to pin hole column (shop: 2")');
            $table->decimal('pin_vertical_spacing_inches', 8, 4)->nullable()
                ->after('pin_setback_back_inches')
                ->comment('Vertical spacing between pin holes (shop: 2")');
            $table->decimal('pin_hole_diameter_mm', 5, 2)->nullable()
                ->after('pin_vertical_spacing_inches')
                ->comment('Pin hole diameter (standard: 5mm)');
            $table->boolean('has_center_support')->default(false)
                ->after('pin_hole_diameter_mm')
                ->comment('Has center pin column (true if width >= 28")');

            // ===== NOTCH SPECIFICATIONS =====
            $table->decimal('notch_depth_inches', 8, 4)->nullable()
                ->after('has_center_support')
                ->comment('Notch depth for shelf pins (3/8" or 5/8")');
            $table->integer('notch_count')->nullable()
                ->after('notch_depth_inches')
                ->comment('Number of notches (4 standard, 6 with center support)');

            // ===== CLEARANCES =====
            $table->decimal('clearance_side_inches', 8, 4)->nullable()
                ->after('notch_count')
                ->comment('Side clearance per side (typically 1/16")');
            $table->decimal('clearance_back_inches', 8, 4)->nullable()
                ->after('clearance_side_inches')
                ->comment('Back clearance (typically 1/4")');

            // ===== EDGE BANDING =====
            $table->boolean('edge_band_front')->default(true)
                ->after('clearance_back_inches')
                ->comment('Front edge banded (shop: always yes)');
            $table->boolean('edge_band_back')->default(false)
                ->after('edge_band_front')
                ->comment('Back edge banded (shop: never)');
            $table->boolean('edge_band_sides')->default(false)
                ->after('edge_band_back')
                ->comment('Side edges banded (shop: never for adjustable)');
            $table->decimal('edge_band_length_inches', 8, 4)->nullable()
                ->after('edge_band_sides')
                ->comment('Linear inches of edge banding needed');

            // ===== HARDWARE =====
            $table->foreignId('shelf_pin_product_id')->nullable()
                ->after('edge_band_length_inches')
                ->constrained('products_products')
                ->nullOnDelete()
                ->comment('Shelf pin product from inventory');
            $table->integer('shelf_pin_quantity')->nullable()
                ->after('shelf_pin_product_id')
                ->comment('Number of pins needed (typically 4 or 6)');

            // ===== CALCULATION METADATA =====
            $table->string('spec_source', 50)->nullable()
                ->after('shelf_pin_quantity')
                ->comment('Source of specs: shop_standard, custom, manual');
            $table->timestamp('dimensions_calculated_at')->nullable()
                ->after('spec_source')
                ->comment('When dimensions were auto-calculated');
        });
    }

    public function down(): void
    {
        Schema::table('projects_shelves', function (Blueprint $table) {
            $table->dropForeign(['shelf_pin_product_id']);
            
            $table->dropColumn([
                // Opening reference
                'opening_width_inches',
                'opening_height_inches',
                'opening_depth_inches',
                // Calculated dimensions
                'cut_width_inches',
                'cut_depth_inches',
                // Pin hole specs
                'pin_setback_front_inches',
                'pin_setback_back_inches',
                'pin_vertical_spacing_inches',
                'pin_hole_diameter_mm',
                'has_center_support',
                // Notch specs
                'notch_depth_inches',
                'notch_count',
                // Clearances
                'clearance_side_inches',
                'clearance_back_inches',
                // Edge banding
                'edge_band_front',
                'edge_band_back',
                'edge_band_sides',
                'edge_band_length_inches',
                // Hardware
                'shelf_pin_product_id',
                'shelf_pin_quantity',
                // Metadata
                'spec_source',
                'dimensions_calculated_at',
            ]);
        });
    }
};
