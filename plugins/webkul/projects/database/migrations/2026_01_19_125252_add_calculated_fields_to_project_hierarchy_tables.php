<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add calculated fields to the project hierarchy tables.
 *
 * Hierarchy: Project → Room → Room Location → Cabinet Run → Cabinet → Section
 *
 * Each level stores aggregate calculations from child entities:
 * - Project: Total LF, total cabinet count, material totals
 * - Room: Room-level aggregates, default construction values
 * - Cabinet Run: Run totals, material quantities
 * - Cabinet Section: Opening calculations, component layouts
 *
 * These fields enable:
 * - Grasshopper data flow with calculated values
 * - Real-time pricing based on calculated dimensions
 * - BOM generation at each level
 * - Audit trail for construction decisions
 *
 * @see App\Services\CabinetCalculatorService
 * @see App\Services\ConstructionStandardsService
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ======================================================
        // PROJECTS - Aggregate calculations for entire project
        // ======================================================
        Schema::table('projects_projects', function (Blueprint $table) {
            // Aggregate linear feet by cabinet type
            $table->decimal('total_base_cabinet_lf', 10, 4)->nullable()->after('estimated_linear_feet')
                ->comment('Total linear feet of base cabinets');
            $table->decimal('total_wall_cabinet_lf', 10, 4)->nullable()->after('total_base_cabinet_lf')
                ->comment('Total linear feet of wall cabinets');
            $table->decimal('total_tall_cabinet_lf', 10, 4)->nullable()->after('total_wall_cabinet_lf')
                ->comment('Total linear feet of tall/pantry cabinets');
            $table->decimal('total_vanity_lf', 10, 4)->nullable()->after('total_tall_cabinet_lf')
                ->comment('Total linear feet of vanity cabinets');

            // Material totals
            $table->decimal('total_sheet_goods_sqft', 10, 2)->nullable()->after('total_vanity_lf')
                ->comment('Total square feet of sheet goods needed');
            $table->decimal('total_solid_wood_bf', 10, 2)->nullable()->after('total_sheet_goods_sqft')
                ->comment('Total board feet of solid wood needed');
            $table->decimal('total_edge_banding_lf', 10, 2)->nullable()->after('total_solid_wood_bf')
                ->comment('Total linear feet of edge banding');

            // Cabinet counts
            $table->integer('total_cabinet_count')->nullable()->after('total_edge_banding_lf')
                ->comment('Total number of cabinets in project');
            $table->integer('total_drawer_count')->nullable()->after('total_cabinet_count')
                ->comment('Total drawers across all cabinets');
            $table->integer('total_door_count')->nullable()->after('total_drawer_count')
                ->comment('Total doors across all cabinets');

            // Last recalculation timestamp
            $table->timestamp('dimensions_calculated_at')->nullable()->after('total_door_count')
                ->comment('When aggregate dimensions were last calculated');

            $table->index('dimensions_calculated_at');
        });

        // ======================================================
        // ROOMS - Room-level aggregates and default depths
        // ======================================================
        Schema::table('projects_rooms', function (Blueprint $table) {
            // Default cabinet depths for this room (can vary by room type)
            $table->decimal('default_base_depth_inches', 8, 4)->nullable()->after('construction_template_id')
                ->comment('Default base cabinet depth for this room');
            $table->decimal('default_wall_depth_inches', 8, 4)->nullable()->after('default_base_depth_inches')
                ->comment('Default wall cabinet depth for this room');
            $table->decimal('default_vanity_depth_inches', 8, 4)->nullable()->after('default_wall_depth_inches')
                ->comment('Default vanity depth for this room');

            // Room-level aggregates
            $table->decimal('room_total_lf', 10, 4)->nullable()->after('default_vanity_depth_inches')
                ->comment('Total linear feet in this room');
            $table->integer('room_cabinet_count')->nullable()->after('room_total_lf')
                ->comment('Total cabinets in this room');
            $table->decimal('room_sheet_goods_sqft', 10, 2)->nullable()->after('room_cabinet_count')
                ->comment('Sheet goods needed for this room');
            $table->decimal('room_solid_wood_bf', 10, 2)->nullable()->after('room_sheet_goods_sqft')
                ->comment('Solid wood needed for this room');

            // Calculation metadata
            $table->timestamp('dimensions_calculated_at')->nullable()->after('room_solid_wood_bf')
                ->comment('When room dimensions were last calculated');

            $table->index('dimensions_calculated_at');
        });

        // ======================================================
        // CABINET RUNS - Run-level calculations
        // ======================================================
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            // Depth breakdown for the run (inherited to cabinets)
            $table->decimal('run_depth_inches', 8, 4)->nullable()->after('cabinet_count_cached')
                ->comment('Standard depth for cabinets in this run');
            $table->decimal('run_internal_depth_inches', 8, 4)->nullable()->after('run_depth_inches')
                ->comment('Internal depth (side panel depth) for this run');
            $table->integer('run_max_slide_length', false, true)->nullable()->after('run_internal_depth_inches')
                ->comment('Maximum slide length that fits this run depth');

            // Aggregate calculations
            $table->decimal('run_total_width_inches', 10, 4)->nullable()->after('run_max_slide_length')
                ->comment('Total width of all cabinets in run');
            $table->decimal('run_sheet_goods_sqft', 10, 2)->nullable()->after('run_total_width_inches')
                ->comment('Sheet goods needed for this run');
            $table->decimal('run_solid_wood_bf', 10, 2)->nullable()->after('run_sheet_goods_sqft')
                ->comment('Solid wood needed for this run');

            // Calculation metadata
            $table->timestamp('dimensions_calculated_at')->nullable()->after('run_solid_wood_bf')
                ->comment('When run dimensions were last calculated');

            $table->index('dimensions_calculated_at');
        });

        // ======================================================
        // CABINET SECTIONS - Section opening calculations
        // ======================================================
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            // Calculated opening dimensions (derived from cabinet + face frame)
            $table->decimal('calculated_opening_width_inches', 8, 4)->nullable()->after('opening_height_inches')
                ->comment('Calculated face frame opening width');
            $table->decimal('calculated_opening_height_inches', 8, 4)->nullable()->after('calculated_opening_width_inches')
                ->comment('Calculated face frame opening height');
            $table->decimal('available_component_height_inches', 8, 4)->nullable()->after('calculated_opening_height_inches')
                ->comment('Height available for components (after rails/gaps)');

            // Component layout calculations
            $table->decimal('total_drawer_height_inches', 8, 4)->nullable()->after('available_component_height_inches')
                ->comment('Combined height of all drawers in section');
            $table->decimal('total_door_height_inches', 8, 4)->nullable()->after('total_drawer_height_inches')
                ->comment('Combined height of all doors in section');
            $table->decimal('unused_height_inches', 8, 4)->nullable()->after('total_door_height_inches')
                ->comment('Height remaining after components');

            // Stretcher tracking for section
            $table->integer('section_stretcher_count')->nullable()->after('unused_height_inches')
                ->comment('Number of stretchers for this section');

            // Validation
            $table->boolean('layout_validated')->default(false)->after('section_stretcher_count')
                ->comment('Whether component layout has been validated');
            $table->string('layout_validation_message', 255)->nullable()->after('layout_validated')
                ->comment('Validation result or error message');

            // Calculation metadata
            $table->timestamp('dimensions_calculated_at')->nullable()->after('layout_validation_message')
                ->comment('When section dimensions were last calculated');

            $table->index(['layout_validated', 'dimensions_calculated_at'], 'cab_sections_validated_calc_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropIndex(['dimensions_calculated_at']);
            $table->dropColumn([
                'total_base_cabinet_lf',
                'total_wall_cabinet_lf',
                'total_tall_cabinet_lf',
                'total_vanity_lf',
                'total_sheet_goods_sqft',
                'total_solid_wood_bf',
                'total_edge_banding_lf',
                'total_cabinet_count',
                'total_drawer_count',
                'total_door_count',
                'dimensions_calculated_at',
            ]);
        });

        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->dropIndex(['dimensions_calculated_at']);
            $table->dropColumn([
                'default_base_depth_inches',
                'default_wall_depth_inches',
                'default_vanity_depth_inches',
                'room_total_lf',
                'room_cabinet_count',
                'room_sheet_goods_sqft',
                'room_solid_wood_bf',
                'dimensions_calculated_at',
            ]);
        });

        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropIndex(['dimensions_calculated_at']);
            $table->dropColumn([
                'run_depth_inches',
                'run_internal_depth_inches',
                'run_max_slide_length',
                'run_total_width_inches',
                'run_sheet_goods_sqft',
                'run_solid_wood_bf',
                'dimensions_calculated_at',
            ]);
        });

        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->dropIndex('cab_sections_validated_calc_idx');
            $table->dropColumn([
                'calculated_opening_width_inches',
                'calculated_opening_height_inches',
                'available_component_height_inches',
                'total_drawer_height_inches',
                'total_door_height_inches',
                'unused_height_inches',
                'section_stretcher_count',
                'layout_validated',
                'layout_validation_message',
                'dimensions_calculated_at',
            ]);
        });
    }
};
