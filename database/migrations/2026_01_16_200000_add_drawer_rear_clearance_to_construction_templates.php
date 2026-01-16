<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds drawer_rear_clearance to construction templates.
     *
     * TCS Standard (verified with Bryan Patton):
     * Total Depth = Face Frame + Drawer + Clearance + Back + Wall Gap
     * 21"         = 1.5"       + 18"    + 0.75"     + 0.75" + 0.5"
     *
     * The drawer_rear_clearance is the 3/4" (0.75") clearance behind the drawer
     * when closed. This is critical for calculating minimum cabinet depth:
     *   min_cabinet_depth = slide_length + drawer_rear_clearance
     *
     * Example: 18" slide needs 18.75" cabinet internal depth (shop minimum).
     *
     * Note: Back panel sits in dado/rabbet, so it does NOT add to internal depth.
     */
    public function up(): void
    {
        Schema::table('projects_construction_templates', function (Blueprint $table) {
            // Add drawer_rear_clearance after back_panel_thickness
            $table->decimal('drawer_rear_clearance', 8, 4)->default(0.75)
                ->after('back_panel_thickness')
                ->comment('Clearance behind drawer when closed (TCS: 3/4"). Min cabinet depth = slide + this.');

            // Also add back_wall_gap if it doesn't exist (related construction standard)
            $table->decimal('back_wall_gap', 8, 4)->default(0.5)
                ->after('drawer_rear_clearance')
                ->comment('Gap from back of cabinet to wall (TCS: 1/2")');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_construction_templates', function (Blueprint $table) {
            $table->dropColumn(['drawer_rear_clearance', 'back_wall_gap']);
        });
    }
};
