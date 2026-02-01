<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Centralizes all cabinet construction constants into the construction_templates table.
     * These values were previously hardcoded across multiple services:
     * - DrawerConfiguratorService
     * - CabinetMathAuditService
     * - CabinetCalculatorService
     * - CabinetXYZService
     * - OpeningConfiguratorService
     * - StretcherCalculator
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_construction_templates')) {
            return;
        }

Schema::table('projects_construction_templates', function (Blueprint $table) {
            // ========================================
            // REVEAL & GAP CONSTANTS
            // ========================================
            $table->decimal('reveal_top', 8, 4)->default(0.125)
                ->after('finished_counter_height')
                ->comment('Top reveal gap (1/8")');
            $table->decimal('reveal_bottom', 8, 4)->default(0.125)
                ->after('reveal_top')
                ->comment('Bottom reveal gap (1/8")');
            $table->decimal('component_gap', 8, 4)->default(0.125)
                ->after('reveal_bottom')
                ->comment('Gap between components like drawers/doors (1/8")');
            $table->decimal('door_side_reveal', 8, 4)->default(0.0625)
                ->after('component_gap')
                ->comment('Door side reveal gap (1/16")');

            // ========================================
            // DRAWER BOX CONSTRUCTION
            // ========================================
            $table->decimal('drawer_material_thickness', 8, 4)->default(0.5)
                ->after('door_side_reveal')
                ->comment('Drawer sides/front/back material thickness (1/2")');
            $table->decimal('drawer_bottom_thickness', 8, 4)->default(0.25)
                ->after('drawer_material_thickness')
                ->comment('Drawer bottom panel thickness (1/4")');
            $table->decimal('drawer_dado_depth', 8, 4)->default(0.25)
                ->after('drawer_bottom_thickness')
                ->comment('Dado depth for drawer bottom (1/4")');
            $table->decimal('drawer_dado_height', 8, 4)->default(0.5)
                ->after('drawer_dado_depth')
                ->comment('Dado height from bottom edge (1/2")');
            $table->decimal('drawer_dado_clearance', 8, 4)->default(0.0625)
                ->after('drawer_dado_height')
                ->comment('Clearance in dado for bottom panel (1/16")');

            // ========================================
            // BLUM TANDEM SLIDE CLEARANCES
            // (Hardware-specific but configurable for different slide brands)
            // ========================================
            $table->decimal('slide_side_deduction', 8, 4)->default(0.625)
                ->after('drawer_dado_clearance')
                ->comment('Total width deduction for drawer slides (5/8" for Blum TANDEM)');
            $table->decimal('slide_top_clearance', 8, 4)->default(0.25)
                ->after('slide_side_deduction')
                ->comment('Top clearance above drawer box (1/4")');
            $table->decimal('slide_bottom_clearance', 8, 4)->default(0.5625)
                ->after('slide_top_clearance')
                ->comment('Bottom clearance below drawer box (9/16")');
            $table->decimal('slide_height_deduction', 8, 4)->default(0.8125)
                ->after('slide_bottom_clearance')
                ->comment('Total height deduction for slides (13/16")');

            // ========================================
            // FINISHED END PANEL
            // ========================================
            $table->decimal('finished_end_gap', 8, 4)->default(0.25)
                ->after('slide_height_deduction')
                ->comment('Gap between cabinet side and end panel (1/4")');
            $table->decimal('finished_end_wall_extension', 8, 4)->default(0.5)
                ->after('finished_end_gap')
                ->comment('End panel extension toward wall for scribe (1/2")');

            // ========================================
            // MINIMUM DIMENSIONS
            // ========================================
            $table->decimal('min_shelf_opening_height', 8, 4)->default(5.5)
                ->after('finished_end_wall_extension')
                ->comment('Minimum shelf opening height (5-1/2")');
            $table->decimal('min_drawer_front_height', 8, 4)->default(4.0)
                ->after('min_shelf_opening_height')
                ->comment('Minimum drawer front height (4")');

            // ========================================
            // ADDITIONAL TCS STANDARDS
            // ========================================
            $table->decimal('false_front_backing_overhang', 8, 4)->default(1.0)
                ->after('min_drawer_front_height')
                ->comment('False front backing overhang beyond face (1")');
            $table->decimal('default_slide_length', 8, 4)->default(18.0)
                ->after('false_front_backing_overhang')
                ->comment('Default drawer slide length (18")');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_construction_templates', function (Blueprint $table) {
            $table->dropColumn([
                'reveal_top',
                'reveal_bottom',
                'component_gap',
                'door_side_reveal',
                'drawer_material_thickness',
                'drawer_bottom_thickness',
                'drawer_dado_depth',
                'drawer_dado_height',
                'drawer_dado_clearance',
                'slide_side_deduction',
                'slide_top_clearance',
                'slide_bottom_clearance',
                'slide_height_deduction',
                'finished_end_gap',
                'finished_end_wall_extension',
                'min_shelf_opening_height',
                'min_drawer_front_height',
                'false_front_backing_overhang',
                'default_slide_length',
            ]);
        });
    }
};
