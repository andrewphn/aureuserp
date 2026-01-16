<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add position fields for stretcher vertical placement.
     *
     * TCS Standard (Bryan Patton, Jan 2025):
     * - Stretcher splits the gap between drawer faces
     * - Position measured from top of box to top of stretcher
     * - Override allows manual CAD-specified positions
     */
    public function up(): void
    {
        Schema::table('projects_stretchers', function (Blueprint $table) {
            // Position from bottom of box (in inches) - calculated or override
            if (!Schema::hasColumn('projects_stretchers', 'position_from_bottom_inches')) {
                $table->decimal('position_from_bottom_inches', 8, 4)->nullable()
                    ->after('position_from_top_inches')
                    ->comment('Distance from bottom of box to TOP of stretcher (inches)');
            }

            // Manual override position (from CAD drawings)
            if (!Schema::hasColumn('projects_stretchers', 'position_override_inches')) {
                $table->decimal('position_override_inches', 8, 4)->nullable()
                    ->after('position_from_bottom_inches')
                    ->comment('Manual override position from top (from CAD)');
            }

            // Track whether position was calculated or manually set
            if (!Schema::hasColumn('projects_stretchers', 'position_source')) {
                $table->string('position_source', 20)->nullable()->default('calculated')
                    ->after('position_override_inches')
                    ->comment('calculated, cad_override, or manual');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_stretchers', function (Blueprint $table) {
            $columns = ['position_from_bottom_inches', 'position_override_inches', 'position_source'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('projects_stretchers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
