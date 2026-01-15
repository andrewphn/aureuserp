<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes projects_cabinet_sections which was missed
     * in the 2025_11_29_133554_rename_cabinet_specifications_to_cabinets_table migration.
     *
     * Renames: cabinet_specification_id → cabinet_id
     */
    public function up(): void
    {
        if (Schema::hasTable('projects_cabinet_sections') && Schema::hasColumn('projects_cabinet_sections', 'cabinet_specification_id')) {
            // Drop the foreign key first
            Schema::table('projects_cabinet_sections', function (Blueprint $table) {
                $table->dropForeign(['cabinet_specification_id']);
            });

            // Rename the column
            Schema::table('projects_cabinet_sections', function (Blueprint $table) {
                $table->renameColumn('cabinet_specification_id', 'cabinet_id');
            });

            // Re-add the foreign key with the new column name
            Schema::table('projects_cabinet_sections', function (Blueprint $table) {
                $table->foreign('cabinet_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('projects_cabinet_sections') && Schema::hasColumn('projects_cabinet_sections', 'cabinet_id')) {
            // Drop the foreign key
            Schema::table('projects_cabinet_sections', function (Blueprint $table) {
                $table->dropForeign(['cabinet_id']);
            });

            // Rename back
            Schema::table('projects_cabinet_sections', function (Blueprint $table) {
                $table->renameColumn('cabinet_id', 'cabinet_specification_id');
            });

            // Re-add the foreign key
            Schema::table('projects_cabinet_sections', function (Blueprint $table) {
                $table->foreign('cabinet_specification_id')
                    ->references('id')
                    ->on('projects_cabinets')
                    ->onDelete('cascade');
            });
        }
    }
};
