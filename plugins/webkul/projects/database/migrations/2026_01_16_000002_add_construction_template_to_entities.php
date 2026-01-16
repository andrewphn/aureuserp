<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds construction_template_id FK to projects, rooms, and cabinets.
     * Enables template inheritance: Cabinet -> Room -> Project -> Default
     */
    public function up(): void
    {
        // Add to projects_projects
        if (!Schema::hasColumn('projects_projects', 'construction_template_id')) {
            Schema::table('projects_projects', function (Blueprint $table) {
                $table->foreignId('construction_template_id')
                    ->nullable()
                    ->after('warehouse_id')
                    ->constrained('projects_construction_templates')
                    ->nullOnDelete();
            });
        }

        // Add to projects_rooms
        if (!Schema::hasColumn('projects_rooms', 'construction_template_id')) {
            Schema::table('projects_rooms', function (Blueprint $table) {
                $table->foreignId('construction_template_id')
                    ->nullable()
                    ->after('finish_option')
                    ->constrained('projects_construction_templates')
                    ->nullOnDelete();
            });
        }

        // Add to projects_cabinets
        if (!Schema::hasColumn('projects_cabinets', 'construction_template_id')) {
            Schema::table('projects_cabinets', function (Blueprint $table) {
                $table->foreignId('construction_template_id')
                    ->nullable()
                    ->after('edge_banding_product_id')
                    ->constrained('projects_construction_templates')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove from projects_cabinets
        if (Schema::hasColumn('projects_cabinets', 'construction_template_id')) {
            Schema::table('projects_cabinets', function (Blueprint $table) {
                $table->dropForeign(['construction_template_id']);
                $table->dropColumn('construction_template_id');
            });
        }

        // Remove from projects_rooms
        if (Schema::hasColumn('projects_rooms', 'construction_template_id')) {
            Schema::table('projects_rooms', function (Blueprint $table) {
                $table->dropForeign(['construction_template_id']);
                $table->dropColumn('construction_template_id');
            });
        }

        // Remove from projects_projects
        if (Schema::hasColumn('projects_projects', 'construction_template_id')) {
            Schema::table('projects_projects', function (Blueprint $table) {
                $table->dropForeign(['construction_template_id']);
                $table->dropColumn('construction_template_id');
            });
        }
    }
};
