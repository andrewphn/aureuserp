<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add position tracking fields to all component tables
 *
 * These fields enable the Opening Configurator to track where each
 * component is positioned within its parent opening (section).
 *
 * Tables affected:
 * - projects_drawers
 * - projects_shelves
 * - projects_doors
 * - projects_pullouts
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 * @see docs/DATABASE_HIERARCHY.md
 */
return new class extends Migration
{
    /**
     * Component tables that need position tracking
     */
    private array $tables = [
        'projects_drawers',
        'projects_shelves',
        'projects_doors',
        'projects_pullouts',
    ];

    public function up(): void
    {
        
        if (!Schema::hasTable('projects_cabinet_components')) {
            return;
        }

foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // ===== VERTICAL POSITION (for stacked components) =====
                $table->decimal('position_in_opening_inches', 8, 4)
                    ->nullable()
                    ->after('sort_order')
                    ->comment('Distance from bottom of opening to component bottom');

                $table->decimal('consumed_height_inches', 8, 4)
                    ->nullable()
                    ->after('position_in_opening_inches')
                    ->comment('Total vertical space consumed (height + applicable gap)');

                // ===== HORIZONTAL POSITION (for side-by-side / splits) =====
                $table->decimal('position_from_left_inches', 8, 4)
                    ->nullable()
                    ->after('consumed_height_inches')
                    ->comment('Distance from left edge of opening');

                $table->decimal('consumed_width_inches', 8, 4)
                    ->nullable()
                    ->after('position_from_left_inches')
                    ->comment('Total horizontal space consumed');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'position_in_opening_inches',
                    'consumed_height_inches',
                    'position_from_left_inches',
                    'consumed_width_inches',
                ]);
            });
        }
    }
};
