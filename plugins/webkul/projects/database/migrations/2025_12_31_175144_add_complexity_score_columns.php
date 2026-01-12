<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds complexity_score, complexity_breakdown, and complexity_calculated_at columns
     * to all tables in the project hierarchy for hierarchical complexity scoring.
     */
    public function up(): void
    {
        // Component level tables
        Schema::table('projects_doors', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('qc_notes')
                ->comment('Calculated complexity score for this door');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of complexity factors');
            $table->timestamp('complexity_calculated_at')->nullable()->after('complexity_breakdown');
        });

        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('qc_notes')
                ->comment('Calculated complexity score for this drawer');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of complexity factors');
            $table->timestamp('complexity_calculated_at')->nullable()->after('complexity_breakdown');
        });

        Schema::table('projects_shelves', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('qc_notes')
                ->comment('Calculated complexity score for this shelf');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of complexity factors');
            $table->timestamp('complexity_calculated_at')->nullable()->after('complexity_breakdown');
        });

        Schema::table('projects_pullouts', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('qc_notes')
                ->comment('Calculated complexity score for this pullout');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of complexity factors');
            $table->timestamp('complexity_calculated_at')->nullable()->after('complexity_breakdown');
        });

        // Cabinet section level
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('notes')
                ->comment('Weighted average complexity of components in this section');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of component complexities');
            $table->integer('component_count_cached')->nullable()->after('complexity_breakdown')
                ->comment('Cached count of components for quick reference');
            $table->timestamp('complexity_calculated_at')->nullable()->after('component_count_cached');
        });

        // Cabinet level
        Schema::table('projects_cabinets', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('shop_notes')
                ->comment('Weighted average complexity of sections in this cabinet');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of section complexities');
            $table->integer('section_count_cached')->nullable()->after('complexity_breakdown')
                ->comment('Cached count of sections for quick reference');
            $table->timestamp('complexity_calculated_at')->nullable()->after('section_count_cached');
        });

        // Cabinet run level
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('finish_option')
                ->comment('Weighted average complexity of cabinets in this run');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of cabinet complexities');
            $table->integer('cabinet_count_cached')->nullable()->after('complexity_breakdown')
                ->comment('Cached count of cabinets for quick reference');
            $table->timestamp('complexity_calculated_at')->nullable()->after('cabinet_count_cached');
        });

        // Room location level
        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('finish_option')
                ->comment('Weighted average complexity of cabinet runs in this location');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of run complexities');
            $table->integer('run_count_cached')->nullable()->after('complexity_breakdown')
                ->comment('Cached count of cabinet runs for quick reference');
            $table->timestamp('complexity_calculated_at')->nullable()->after('run_count_cached');
        });

        // Room level
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->decimal('complexity_score', 6, 2)->nullable()->after('finish_option')
                ->comment('Weighted average complexity of locations in this room');
            $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                ->comment('JSON breakdown of location complexities');
            $table->integer('location_count_cached')->nullable()->after('complexity_breakdown')
                ->comment('Cached count of locations for quick reference');
            $table->timestamp('complexity_calculated_at')->nullable()->after('location_count_cached');
        });

        // Project level - complexity_score already exists, only add missing columns
        Schema::table('projects_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects_projects', 'complexity_breakdown')) {
                $table->json('complexity_breakdown')->nullable()->after('complexity_score')
                    ->comment('JSON breakdown of room complexities');
            }
            if (!Schema::hasColumn('projects_projects', 'room_count_cached')) {
                $table->integer('room_count_cached')->nullable()->after('complexity_breakdown')
                    ->comment('Cached count of rooms for quick reference');
            }
            if (!Schema::hasColumn('projects_projects', 'complexity_calculated_at')) {
                $table->timestamp('complexity_calculated_at')->nullable()->after('room_count_cached');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_doors', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'complexity_calculated_at']);
        });

        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'complexity_calculated_at']);
        });

        Schema::table('projects_shelves', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'complexity_calculated_at']);
        });

        Schema::table('projects_pullouts', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'complexity_calculated_at']);
        });

        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'component_count_cached', 'complexity_calculated_at']);
        });

        Schema::table('projects_cabinets', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'section_count_cached', 'complexity_calculated_at']);
        });

        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'cabinet_count_cached', 'complexity_calculated_at']);
        });

        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'run_count_cached', 'complexity_calculated_at']);
        });

        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->dropColumn(['complexity_score', 'complexity_breakdown', 'location_count_cached', 'complexity_calculated_at']);
        });

        // Only drop the columns we added (not complexity_score which already existed)
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn(['complexity_breakdown', 'room_count_cached', 'complexity_calculated_at']);
        });
    }
};
