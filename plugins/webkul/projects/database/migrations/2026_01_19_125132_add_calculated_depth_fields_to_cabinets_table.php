<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add calculated depth breakdown fields to cabinets table.
 *
 * TCS DEPTH FORMULA (Bryan Patton, Jan 2025-2026):
 * Total Depth = Face Frame + Drawer + Clearance + Back + Wall Gap
 * Example: 21" = 1.5" + 18" + 0.25" + 0.75" + 0.5"
 *
 * These fields store the calculated breakdown from CabinetCalculatorService
 * using ConstructionStandardsService values. The breakdown enables:
 * - Cut list generation with exact dimensions
 * - Grasshopper geometry generation
 * - Drawer slide compatibility validation
 * - Construction audit trail
 *
 * @see App\Services\CabinetCalculatorService::calculateRequiredCabinetDepth()
 * @see App\Services\ConstructionStandardsService
 * @see sample/9 Austin Lane/cabinet_build.py
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects_cabinets', function (Blueprint $table) {
            // Depth breakdown fields (TCS standard formula)
            $table->decimal('face_frame_depth_inches', 8, 4)->nullable()->after('depth_inches')
                ->comment('Face frame stile depth (typically 1.5")');
            $table->decimal('internal_depth_inches', 8, 4)->nullable()->after('face_frame_depth_inches')
                ->comment('Side panel depth = drawer + clearance (typically 18.25")');
            $table->decimal('drawer_depth_inches', 8, 4)->nullable()->after('internal_depth_inches')
                ->comment('Drawer slide length (9, 12, 15, 18, or 21")');
            $table->decimal('drawer_clearance_inches', 8, 4)->nullable()->after('drawer_depth_inches')
                ->comment('Clearance behind drawer (typically 0.25-0.75")');
            $table->decimal('back_wall_gap_inches', 8, 4)->nullable()->after('drawer_clearance_inches')
                ->comment('Gap between back panel and wall (typically 0.5")');

            // Box height breakdown
            $table->decimal('box_height_inches', 8, 4)->nullable()->after('back_wall_gap_inches')
                ->comment('Cabinet height minus toe kick');

            // Calculated validation fields
            $table->boolean('depth_validated')->default(false)->after('box_height_inches')
                ->comment('Whether depth was validated for drawer slides');
            $table->string('depth_validation_message', 255)->nullable()->after('depth_validated')
                ->comment('Validation result message from calculator');
            $table->integer('max_slide_length_inches')->nullable()->after('depth_validation_message')
                ->comment('Maximum drawer slide that fits this cabinet depth');

            // Calculation metadata
            $table->timestamp('calculated_at')->nullable()->after('max_slide_length_inches')
                ->comment('When dimensions were last calculated by service');

            // Index for querying cabinets by calculation status
            $table->index('depth_validated');
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinets', function (Blueprint $table) {
            $table->dropIndex(['depth_validated']);
            $table->dropIndex(['calculated_at']);

            $table->dropColumn([
                'face_frame_depth_inches',
                'internal_depth_inches',
                'drawer_depth_inches',
                'drawer_clearance_inches',
                'back_wall_gap_inches',
                'box_height_inches',
                'depth_validated',
                'depth_validation_message',
                'max_slide_length_inches',
                'calculated_at',
            ]);
        });
    }
};
