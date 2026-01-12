<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add project, BOM, and hardware requirement tracking to purchase order lines.
 *
 * This enables:
 * - Line-level project assignment (each line can be for a different project)
 * - Direct BOM linking (for sheet goods, panels, etc.)
 * - Direct hardware requirement linking (for hinges, slides, etc.)
 * - Automatic allocation tracking when goods are received
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run if the table exists (purchases plugin installed)
        if (!Schema::hasTable('purchases_order_lines')) {
            return;
        }

        Schema::table('purchases_order_lines', function (Blueprint $table) {
            // Project assignment - which project this line item is for
            if (!Schema::hasColumn('purchases_order_lines', 'project_id')) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->after('order_point_id')
                    ->constrained('projects_projects')
                    ->nullOnDelete();
            }

            // BOM link - for sheet goods/materials linked to cabinet BOM entries
            if (!Schema::hasColumn('purchases_order_lines', 'bom_id')) {
                $table->foreignId('bom_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('projects_bom')
                    ->nullOnDelete();
            }

            // Hardware requirement link - for hardware linked to specific cabinet hardware needs
            if (!Schema::hasColumn('purchases_order_lines', 'hardware_requirement_id')) {
                $table->foreignId('hardware_requirement_id')
                    ->nullable()
                    ->after('bom_id')
                    ->constrained('hardware_requirements')
                    ->nullOnDelete();
            }
        });

        // Add indexes for common queries
        Schema::table('purchases_order_lines', function (Blueprint $table) {
            // Index for finding all order lines for a project
            if (!Schema::hasIndex('purchases_order_lines', 'purchases_order_lines_project_id_index')) {
                $table->index('project_id', 'purchases_order_lines_project_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases_order_lines', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('purchases_order_lines', 'hardware_requirement_id')) {
                $table->dropForeign(['hardware_requirement_id']);
                $table->dropColumn('hardware_requirement_id');
            }

            if (Schema::hasColumn('purchases_order_lines', 'bom_id')) {
                $table->dropForeign(['bom_id']);
                $table->dropColumn('bom_id');
            }

            if (Schema::hasColumn('purchases_order_lines', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }
};
