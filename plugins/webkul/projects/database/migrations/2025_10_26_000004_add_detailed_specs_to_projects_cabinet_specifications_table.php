<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     *
     * Individual cabinet specifications (Levi's assembly workbench level)
     * Example: "B36 - Base Cabinet with 3 Drawers"
     * This is where Levi lives during the build - precise dimensions, hardware, special features.
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Cabinet Identification & Nomenclature
            // NOTE: cabinet_code, sequence_in_run (as position_in_run), cabinet_type,
            // width_inches, height_inches, depth_inches already exist from previous migrations

            // Only add toe_kick dimensions (new columns)
            $table->decimal('toe_kick_height', 8, 3)->nullable()
                ->comment('Toe kick height (typically 4.5")');
            $table->decimal('toe_kick_depth', 8, 3)->nullable()
                ->comment('Toe kick setback (typically 3")');

            // Box Construction
            $table->string('box_material', 100)->nullable()
                ->comment('Plywood type: 3/4 birch, 3/4 maple, 1/2 birch back');
            $table->decimal('box_thickness', 5, 3)->nullable()
                ->comment('Material thickness in inches (0.75, 0.5, etc.)');
            $table->string('joinery_method', 50)->nullable()
                ->comment('dado, dowel, pocket_screw, etc.');
            $table->boolean('has_back_panel')->default(true)
                ->comment('Full back panel vs open back');
            $table->decimal('back_panel_thickness', 5, 3)->nullable()
                ->comment('Back thickness (typically 0.25" or 0.5")');

            // Face Frame Details
            $table->boolean('has_face_frame')->default(true)
                ->comment('Face frame construction (TCS standard)');
            $table->decimal('face_frame_stile_width', 5, 3)->nullable()
                ->comment('Vertical stile width (typically 1.5")');
            $table->decimal('face_frame_rail_width', 5, 3)->nullable()
                ->comment('Horizontal rail width (typically 1.5" or 2.5")');
            $table->string('face_frame_material', 100)->nullable()
                ->comment('Solid wood species for face frame');
            $table->boolean('beaded_face_frame')->default(false)
                ->comment('Beaded inset detail (affects tier pricing)');

            // Doors Configuration
            $table->integer('door_count')->default(0)
                ->comment('Number of doors on this cabinet');
            $table->string('door_style', 100)->nullable()
                ->comment('flat_panel, shaker, beaded, reeded, glass, etc.');
            $table->string('door_mounting', 50)->nullable()
                ->comment('inset, full_overlay, partial_overlay');
            $table->text('door_sizes_json')->nullable()
                ->comment('JSON array: [{width: 17.75, height: 30, qty: 2, hinge_side: "left"}]');
            $table->boolean('has_glass_doors')->default(false)
                ->comment('Glass panel doors present');
            $table->string('glass_type', 100)->nullable()
                ->comment('clear, seeded, frosted, mullioned, etc.');

            // Drawer Configuration
            $table->integer('drawer_count')->default(0)
                ->comment('Number of drawers in this cabinet');
            $table->text('drawer_sizes_json')->nullable()
                ->comment('JSON: [{width: 33.5, depth: 21, height: 5, type: "standard"}]');
            $table->boolean('dovetail_drawers')->default(true)
                ->comment('Dovetail vs pocket screw construction');
            $table->string('drawer_box_material', 100)->nullable()
                ->comment('Drawer box material: maple, birch, etc.');
            $table->decimal('drawer_box_thickness', 5, 3)->nullable()
                ->comment('Drawer side thickness (typically 0.5" or 0.75")');
            $table->boolean('drawer_soft_close')->default(true)
                ->comment('Soft close drawer slides');

            // Shelving
            $table->integer('adjustable_shelf_count')->default(0)
                ->comment('Number of adjustable shelves');
            $table->integer('fixed_shelf_count')->default(0)
                ->comment('Number of fixed/dado shelves');
            $table->decimal('shelf_thickness', 5, 3)->nullable()
                ->comment('Shelf thickness (typically 0.75")');
            $table->string('shelf_material', 100)->nullable()
                ->comment('Shelf material: plywood, solid edge, etc.');
            $table->integer('shelf_pin_holes')->default(0)
                ->comment('Total shelf pin holes for adjustability');

            // Hardware Specifications (per cabinet)
            $table->string('hinge_model', 100)->nullable()
                ->comment('Blum model: 71B9790, 71B3550, etc.');
            $table->integer('hinge_quantity')->default(0)
                ->comment('Number of hinges needed');
            $table->string('slide_model', 100)->nullable()
                ->comment('Blum drawer slide model: Tandem, Undermount, etc.');
            $table->integer('slide_quantity')->default(0)
                ->comment('Number of slide pairs needed');
            $table->text('specialty_hardware_json')->nullable()
                ->comment('JSON: Rev-a-Shelf, Lemans, pullouts, etc.');

            // Rev-a-Shelf & Accessories
            $table->boolean('has_pullout')->default(false)
                ->comment('Has pullout shelf/basket');
            $table->string('pullout_model', 100)->nullable()
                ->comment('Rev-a-Shelf model number');
            $table->boolean('has_lazy_susan')->default(false)
                ->comment('Corner lazy susan');
            $table->string('lazy_susan_model', 100)->nullable()
                ->comment('Lazy susan model (Lemans, full round, etc.)');
            $table->boolean('has_tray_dividers')->default(false)
                ->comment('Vertical tray dividers');
            $table->boolean('has_spice_rack')->default(false)
                ->comment('Pullout spice rack');
            $table->text('interior_accessories_json')->nullable()
                ->comment('JSON: cutlery dividers, knife blocks, etc.');

            // Special Features & Modifications
            $table->boolean('appliance_panel')->default(false)
                ->comment('Panel for dishwasher/fridge');
            $table->string('appliance_type', 100)->nullable()
                ->comment('Appliance this panel matches');
            $table->boolean('has_microwave_shelf')->default(false)
                ->comment('Built-in microwave shelf');
            $table->boolean('has_trash_pullout')->default(false)
                ->comment('Trash/recycling pullout');
            $table->boolean('has_hamper')->default(false)
                ->comment('Laundry hamper');
            $table->boolean('has_wine_rack')->default(false)
                ->comment('Built-in wine storage');
            $table->text('special_features_json')->nullable()
                ->comment('JSON: custom features, modifications');

            // Cutouts & Modifications
            $table->boolean('has_sink_cutout')->default(false)
                ->comment('Sink cutout required');
            $table->text('sink_dimensions_json')->nullable()
                ->comment('JSON: sink template dimensions');
            $table->boolean('has_cooktop_cutout')->default(false)
                ->comment('Cooktop cutout');
            $table->boolean('has_electrical_cutouts')->default(false)
                ->comment('Outlets, switches, etc.');
            $table->text('cutout_notes')->nullable()
                ->comment('Cutout specifications and locations');

            // Pricing & Complexity
            $table->integer('complexity_tier')->nullable()
                ->comment('1-5 pricing tier for this specific cabinet');
            $table->decimal('base_price_per_lf', 8, 2)->nullable()
                ->comment('Base $/LF rate: $138-$225 based on tier');
            $table->decimal('material_upgrade_per_lf', 8, 2)->nullable()
                ->comment('Material $/LF: $138-$185 based on wood');
            $table->decimal('cabinet_linear_feet', 8, 2)->nullable()
                ->comment('Width converted to LF for pricing');
            $table->decimal('estimated_cabinet_price', 10, 2)->nullable()
                ->comment('Calculated: LF × (base + material)');

            // Assembly Instructions
            $table->text('assembly_notes')->nullable()
                ->comment('Special assembly instructions for shop');
            $table->text('installation_notes')->nullable()
                ->comment('On-site installation considerations');
            $table->boolean('requires_scribing')->default(false)
                ->comment('Requires site scribing for fit');
            $table->text('hardware_installation_notes')->nullable()
                ->comment('Special hardware installation steps');

            // Material Takeoff (calculated for ordering)
            $table->decimal('plywood_sqft', 8, 2)->nullable()
                ->comment('Calculated sheet goods for this cabinet');
            $table->decimal('solid_wood_bf', 8, 2)->nullable()
                ->comment('Calculated board feet solid wood');
            $table->decimal('edge_banding_lf', 8, 2)->nullable()
                ->comment('Linear feet of edge banding needed');

            // Production Tracking
            $table->timestamp('cnc_cut_at')->nullable()
                ->comment('When parts were CNC cut');
            $table->timestamp('assembled_at')->nullable()
                ->comment('When cabinet was assembled');
            $table->timestamp('sanded_at')->nullable()
                ->comment('When sanding completed');
            $table->timestamp('finished_at')->nullable()
                ->comment('When finishing completed');
            $table->foreignId('assembled_by_user_id')->nullable()->constrained('users')
                ->comment('Craftsman who assembled this cabinet');

            // QC per Cabinet
            $table->boolean('qc_passed')->nullable()
                ->comment('Cabinet passed QC inspection');
            $table->text('qc_issues')->nullable()
                ->comment('Any quality issues found');
            $table->timestamp('qc_inspected_at')->nullable()
                ->comment('When inspected');

            // Indexes for common queries
            // Note: cabinet_type column may not exist in all installations, so we skip that index
            // $table->index(['cabinet_type', 'complexity_tier'], 'idx_cabinet_type_tier');
            $table->index(['assembled_at', 'finished_at'], 'idx_cabinet_production');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Note: idx_cabinet_type_tier may not exist
            // $table->dropIndex('idx_cabinet_type_tier');
            $table->dropIndex('idx_cabinet_production');

            $table->dropForeign(['assembled_by_user_id']);

            $table->dropColumn([
                // NOTE: Not dropping cabinet_code, sequence_in_run, cabinet_type,
                // width_inches, height_inches, depth_inches as they existed before this migration
                'toe_kick_height',
                'toe_kick_depth',
                'box_material',
                'box_thickness',
                'joinery_method',
                'has_back_panel',
                'back_panel_thickness',
                'has_face_frame',
                'face_frame_stile_width',
                'face_frame_rail_width',
                'face_frame_material',
                'beaded_face_frame',
                'door_count',
                'door_style',
                'door_mounting',
                'door_sizes_json',
                'has_glass_doors',
                'glass_type',
                'drawer_count',
                'drawer_sizes_json',
                'dovetail_drawers',
                'drawer_box_material',
                'drawer_box_thickness',
                'drawer_soft_close',
                'adjustable_shelf_count',
                'fixed_shelf_count',
                'shelf_thickness',
                'shelf_material',
                'shelf_pin_holes',
                'hinge_model',
                'hinge_quantity',
                'slide_model',
                'slide_quantity',
                'specialty_hardware_json',
                'has_pullout',
                'pullout_model',
                'has_lazy_susan',
                'lazy_susan_model',
                'has_tray_dividers',
                'has_spice_rack',
                'interior_accessories_json',
                'appliance_panel',
                'appliance_type',
                'has_microwave_shelf',
                'has_trash_pullout',
                'has_hamper',
                'has_wine_rack',
                'special_features_json',
                'has_sink_cutout',
                'sink_dimensions_json',
                'has_cooktop_cutout',
                'has_electrical_cutouts',
                'cutout_notes',
                'complexity_tier',
                'base_price_per_lf',
                'material_upgrade_per_lf',
                'cabinet_linear_feet',
                'estimated_cabinet_price',
                'assembly_notes',
                'installation_notes',
                'requires_scribing',
                'hardware_installation_notes',
                'plywood_sqft',
                'solid_wood_bf',
                'edge_banding_lf',
                'cnc_cut_at',
                'assembled_at',
                'sanded_at',
                'finished_at',
                'assembled_by_user_id',
                'qc_passed',
                'qc_issues',
                'qc_inspected_at',
            ]);
        });
    }
};
