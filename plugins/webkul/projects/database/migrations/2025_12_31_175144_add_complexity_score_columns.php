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
        // Skip if main project tables don't exist
        if (!Schema::hasTable('projects_doors')) {
            return;
        }

        // Helper to add complexity columns if they don't exist
        $addComplexityColumns = function (Blueprint $table, string $tableName, string $comment, ?string $countColumn = null) {
            if (!Schema::hasColumn($tableName, 'complexity_score')) {
                $table->decimal('complexity_score', 6, 2)->nullable()
                    ->comment($comment);
            }
            if (!Schema::hasColumn($tableName, 'complexity_breakdown')) {
                $table->json('complexity_breakdown')->nullable()
                    ->comment('JSON breakdown of complexity factors');
            }
            if ($countColumn && !Schema::hasColumn($tableName, $countColumn)) {
                $table->integer($countColumn)->nullable()
                    ->comment("Cached count for quick reference");
            }
            if (!Schema::hasColumn($tableName, 'complexity_calculated_at')) {
                $table->timestamp('complexity_calculated_at')->nullable();
            }
        };

        // Component level tables
        Schema::table('projects_doors', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_doors', 'Calculated complexity score for this door'));

        Schema::table('projects_drawers', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_drawers', 'Calculated complexity score for this drawer'));

        Schema::table('projects_shelves', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_shelves', 'Calculated complexity score for this shelf'));

        Schema::table('projects_pullouts', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_pullouts', 'Calculated complexity score for this pullout'));

        // Cabinet section level
        Schema::table('projects_cabinet_sections', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_cabinet_sections', 'Weighted average complexity of components', 'component_count_cached'));

        // Cabinet level (supports both old and new table names)
        $cabinetTable = Schema::hasTable('projects_cabinets') ? 'projects_cabinets' : 'projects_cabinet_specifications';
        if (Schema::hasTable($cabinetTable)) {
            Schema::table($cabinetTable, fn(Blueprint $table) =>
                $addComplexityColumns($table, $cabinetTable, 'Weighted average complexity of sections', 'section_count_cached'));
        }

        // Cabinet run level
        Schema::table('projects_cabinet_runs', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_cabinet_runs', 'Weighted average complexity of cabinets', 'cabinet_count_cached'));

        // Room location level
        Schema::table('projects_room_locations', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_room_locations', 'Weighted average complexity of cabinet runs', 'run_count_cached'));

        // Room level
        Schema::table('projects_rooms', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_rooms', 'Weighted average complexity of locations', 'location_count_cached'));

        // Project level
        Schema::table('projects_projects', fn(Blueprint $table) =>
            $addComplexityColumns($table, 'projects_projects', 'Weighted average complexity of rooms', 'room_count_cached'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'projects_doors' => ['complexity_score', 'complexity_breakdown', 'complexity_calculated_at'],
            'projects_drawers' => ['complexity_score', 'complexity_breakdown', 'complexity_calculated_at'],
            'projects_shelves' => ['complexity_score', 'complexity_breakdown', 'complexity_calculated_at'],
            'projects_pullouts' => ['complexity_score', 'complexity_breakdown', 'complexity_calculated_at'],
            'projects_cabinet_sections' => ['complexity_score', 'complexity_breakdown', 'component_count_cached', 'complexity_calculated_at'],
            'projects_cabinets' => ['complexity_score', 'complexity_breakdown', 'section_count_cached', 'complexity_calculated_at'],
            'projects_cabinet_runs' => ['complexity_score', 'complexity_breakdown', 'cabinet_count_cached', 'complexity_calculated_at'],
            'projects_room_locations' => ['complexity_score', 'complexity_breakdown', 'run_count_cached', 'complexity_calculated_at'],
            'projects_rooms' => ['complexity_score', 'complexity_breakdown', 'location_count_cached', 'complexity_calculated_at'],
            'projects_projects' => ['complexity_breakdown', 'room_count_cached', 'complexity_calculated_at'],
        ];

        foreach ($tables as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($columns, $tableName) {
                    $existing = array_filter($columns, fn($col) => Schema::hasColumn($tableName, $col));
                    if (!empty($existing)) {
                        $table->dropColumn($existing);
                    }
                });
            }
        }
    }
};
