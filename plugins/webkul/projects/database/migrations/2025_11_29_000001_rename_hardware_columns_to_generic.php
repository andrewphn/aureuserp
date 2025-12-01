<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes brand-specific column names (blum_*) and replaces
     * them with generic names to support multi-brand hardware tracking.
     *
     * The hardware_requirements table already exists with generic manufacturer/model
     * columns for detailed tracking. This migration makes the summary columns on
     * cabinet_runs also brand-agnostic.
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            // Rename brand-specific columns to generic names
            $table->renameColumn('blum_hinges_total', 'hinges_count');
            $table->renameColumn('blum_slides_total', 'slides_count');
            $table->renameColumn('shelf_pins_total', 'shelf_pins_count');
        });

        // Add new column for pullouts in a separate statement (some DBs require this)
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->integer('pullouts_count')->default(0)->after('shelf_pins_count')
                ->comment('Total pullouts/organizers needed for run');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropColumn('pullouts_count');
        });

        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            // Rename back to original brand-specific names
            $table->renameColumn('hinges_count', 'blum_hinges_total');
            $table->renameColumn('slides_count', 'blum_slides_total');
            $table->renameColumn('shelf_pins_count', 'shelf_pins_total');
        });
    }
};
