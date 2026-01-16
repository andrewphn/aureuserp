<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * TCS Rule (Bryan Patton, Jan 2025):
     * All false fronts have backing that doubles as stretcher.
     * Rename backing_rail fields to backing fields and add backing_is_stretcher.
     */
    public function up(): void
    {
        Schema::table('projects_false_fronts', function (Blueprint $table) {
            // Rename backing_rail fields to backing fields
            if (Schema::hasColumn('projects_false_fronts', 'has_backing_rail')) {
                $table->renameColumn('has_backing_rail', 'has_backing');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_rail_height_inches')) {
                $table->renameColumn('backing_rail_height_inches', 'backing_height_inches');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_rail_thickness_inches')) {
                $table->renameColumn('backing_rail_thickness_inches', 'backing_thickness_inches');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_rail_material')) {
                $table->renameColumn('backing_rail_material', 'backing_material');
            }

            // Drop old fields that are no longer needed
            if (Schema::hasColumn('projects_false_fronts', 'backing_rail_width_inches')) {
                $table->dropColumn('backing_rail_width_inches');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_rail_position')) {
                $table->dropColumn('backing_rail_position');
            }

            // Rename production tracking fields
            if (Schema::hasColumn('projects_false_fronts', 'backing_rail_cut_at')) {
                $table->renameColumn('backing_rail_cut_at', 'backing_cut_at');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_rail_installed_at')) {
                $table->renameColumn('backing_rail_installed_at', 'backing_installed_at');
            }

            // Add new field: backing doubles as stretcher (TCS rule: always true)
            if (!Schema::hasColumn('projects_false_fronts', 'backing_is_stretcher')) {
                $table->boolean('backing_is_stretcher')->default(true)
                    ->after('backing_material')
                    ->comment('TCS: Backing serves dual purpose as stretcher');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_false_fronts', function (Blueprint $table) {
            // Reverse renames
            if (Schema::hasColumn('projects_false_fronts', 'has_backing')) {
                $table->renameColumn('has_backing', 'has_backing_rail');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_height_inches')) {
                $table->renameColumn('backing_height_inches', 'backing_rail_height_inches');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_thickness_inches')) {
                $table->renameColumn('backing_thickness_inches', 'backing_rail_thickness_inches');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_material')) {
                $table->renameColumn('backing_material', 'backing_rail_material');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_cut_at')) {
                $table->renameColumn('backing_cut_at', 'backing_rail_cut_at');
            }
            if (Schema::hasColumn('projects_false_fronts', 'backing_installed_at')) {
                $table->renameColumn('backing_installed_at', 'backing_rail_installed_at');
            }

            // Re-add dropped columns
            if (!Schema::hasColumn('projects_false_fronts', 'backing_rail_width_inches')) {
                $table->decimal('backing_rail_width_inches', 8, 4)->nullable();
            }
            if (!Schema::hasColumn('projects_false_fronts', 'backing_rail_position')) {
                $table->string('backing_rail_position', 20)->nullable();
            }

            // Drop new column
            if (Schema::hasColumn('projects_false_fronts', 'backing_is_stretcher')) {
                $table->dropColumn('backing_is_stretcher');
            }
        });
    }
};
