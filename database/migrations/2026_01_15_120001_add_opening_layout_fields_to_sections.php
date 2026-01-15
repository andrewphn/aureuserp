
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add opening layout configuration fields to projects_cabinet_sections
 *
 * These fields enable the Opening Configurator to track space usage
 * and configure how components are arranged within an opening.
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 * @see docs/DATABASE_HIERARCHY.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            // ===== SPACE TRACKING (calculated fields) =====
            $table->decimal('total_consumed_height_inches', 8, 4)
                ->nullable()
                ->after('opening_height_inches')
                ->comment('Sum of all component heights + gaps');

            $table->decimal('total_consumed_width_inches', 8, 4)
                ->nullable()
                ->after('total_consumed_height_inches')
                ->comment('Sum of widths for horizontal layout');

            $table->decimal('remaining_height_inches', 8, 4)
                ->nullable()
                ->after('total_consumed_width_inches')
                ->comment('opening_height - consumed_height');

            $table->decimal('remaining_width_inches', 8, 4)
                ->nullable()
                ->after('remaining_height_inches')
                ->comment('opening_width - consumed_width');

            // ===== LAYOUT CONFIGURATION =====
            $table->enum('layout_direction', ['vertical', 'horizontal', 'grid'])
                ->default('vertical')
                ->after('remaining_width_inches')
                ->comment('How components are arranged in opening');

            // ===== GAP/REVEAL SETTINGS (shop standards) =====
            $table->decimal('top_reveal_inches', 8, 4)
                ->default(0.125)
                ->after('layout_direction')
                ->comment('Gap at top of opening (default 1/8")');

            $table->decimal('bottom_reveal_inches', 8, 4)
                ->default(0.125)
                ->after('top_reveal_inches')
                ->comment('Gap at bottom of opening (default 1/8")');

            $table->decimal('component_gap_inches', 8, 4)
                ->default(0.125)
                ->after('bottom_reveal_inches')
                ->comment('Gap between components (default 1/8")');
        });
    }

    public function down(): void
    {
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->dropColumn([
                'total_consumed_height_inches',
                'total_consumed_width_inches',
                'remaining_height_inches',
                'remaining_width_inches',
                'layout_direction',
                'top_reveal_inches',
                'bottom_reveal_inches',
                'component_gap_inches',
            ]);
        });
    }
};
