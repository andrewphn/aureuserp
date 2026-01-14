<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add detailed cut list fields to projects_drawers table.
 * 
 * These fields support complete drawer box specification for:
 * - Blum TANDEM 563H slides with 1/2" or 5/8" drawer sides
 * - Dovetail construction method
 * - 1/4" bottom panel in dado groove
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects_drawers', function (Blueprint $table) {
            // ===== OPENING REFERENCE (input dimensions) =====
            $table->decimal('opening_width_inches', 8, 4)->nullable()
                ->after('box_height_inches')
                ->comment('Cabinet opening width - source dimension');
            $table->decimal('opening_height_inches', 8, 4)->nullable()
                ->after('opening_width_inches')
                ->comment('Cabinet opening height - source dimension');
            $table->decimal('opening_depth_inches', 8, 4)->nullable()
                ->after('opening_height_inches')
                ->comment('Cabinet opening depth - source dimension');

            // ===== BOX DIMENSIONS (calculated) =====
            $table->decimal('box_outside_width_inches', 8, 4)->nullable()
                ->after('opening_depth_inches')
                ->comment('Drawer box outside width (e.g., 11-3/8" for 12" opening with 1/2" sides)');
            $table->decimal('box_inside_width_inches', 8, 4)->nullable()
                ->after('box_outside_width_inches')
                ->comment('Drawer box inside width (usable interior space)');

            // ===== MATERIAL SPECIFICATIONS =====
            $table->decimal('side_thickness_inches', 8, 4)->nullable()
                ->after('box_material')
                ->comment('Side/front/back material thickness (0.5 or 0.625)');
            $table->decimal('bottom_thickness_inches', 8, 4)->nullable()
                ->after('side_thickness_inches')
                ->comment('Bottom panel thickness (typically 0.25)');

            // ===== DADO SPECIFICATIONS =====
            $table->decimal('dado_depth_inches', 8, 4)->nullable()
                ->after('bottom_thickness_inches')
                ->comment('Dado groove depth for bottom panel');
            $table->decimal('dado_width_inches', 8, 4)->nullable()
                ->after('dado_depth_inches')
                ->comment('Dado groove width (matches bottom thickness)');
            $table->decimal('dado_height_inches', 8, 4)->nullable()
                ->after('dado_width_inches')
                ->comment('Dado position - height from bottom edge of sides');

            // ===== CUT LIST - SIDE PIECES =====
            $table->decimal('side_cut_height_inches', 8, 4)->nullable()
                ->after('dado_height_inches')
                ->comment('Side piece cut height');
            $table->decimal('side_cut_length_inches', 8, 4)->nullable()
                ->after('side_cut_height_inches')
                ->comment('Side piece cut length (= box depth)');

            // ===== CUT LIST - FRONT/BACK PIECES =====
            $table->decimal('front_cut_height_inches', 8, 4)->nullable()
                ->after('side_cut_length_inches')
                ->comment('Front piece cut height');
            $table->decimal('front_cut_width_inches', 8, 4)->nullable()
                ->after('front_cut_height_inches')
                ->comment('Front piece cut width (fits between sides for dovetail)');
            $table->decimal('back_cut_height_inches', 8, 4)->nullable()
                ->after('front_cut_width_inches')
                ->comment('Back piece cut height');
            $table->decimal('back_cut_width_inches', 8, 4)->nullable()
                ->after('back_cut_height_inches')
                ->comment('Back piece cut width (fits between sides for dovetail)');

            // ===== CUT LIST - BOTTOM PANEL =====
            $table->decimal('bottom_cut_width_inches', 8, 4)->nullable()
                ->after('back_cut_width_inches')
                ->comment('Bottom panel cut width (includes dado allowance)');
            $table->decimal('bottom_cut_depth_inches', 8, 4)->nullable()
                ->after('bottom_cut_width_inches')
                ->comment('Bottom panel cut depth (includes dado allowance)');

            // ===== BLUM CLEARANCES APPLIED =====
            $table->decimal('clearance_side_inches', 8, 4)->nullable()
                ->after('bottom_cut_depth_inches')
                ->comment('Side clearance/deduction applied');
            $table->decimal('clearance_top_inches', 8, 4)->nullable()
                ->after('clearance_side_inches')
                ->comment('Top clearance applied');
            $table->decimal('clearance_bottom_inches', 8, 4)->nullable()
                ->after('clearance_top_inches')
                ->comment('Bottom clearance applied');

            // ===== CALCULATION METADATA =====
            $table->string('slide_spec_source', 50)->nullable()
                ->after('clearance_bottom_inches')
                ->comment('Source of slide specs: blum_563h, database, manual');
            $table->timestamp('dimensions_calculated_at')->nullable()
                ->after('slide_spec_source')
                ->comment('When dimensions were auto-calculated');
        });
    }

    public function down(): void
    {
        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->dropColumn([
                // Opening reference
                'opening_width_inches',
                'opening_height_inches', 
                'opening_depth_inches',
                // Box dimensions
                'box_outside_width_inches',
                'box_inside_width_inches',
                // Material specs
                'side_thickness_inches',
                'bottom_thickness_inches',
                // Dado specs
                'dado_depth_inches',
                'dado_width_inches',
                'dado_height_inches',
                // Cut list - sides
                'side_cut_height_inches',
                'side_cut_length_inches',
                // Cut list - front/back
                'front_cut_height_inches',
                'front_cut_width_inches',
                'back_cut_height_inches',
                'back_cut_width_inches',
                // Cut list - bottom
                'bottom_cut_width_inches',
                'bottom_cut_depth_inches',
                // Clearances
                'clearance_side_inches',
                'clearance_top_inches',
                'clearance_bottom_inches',
                // Metadata
                'slide_spec_source',
                'dimensions_calculated_at',
            ]);
        });
    }
};
