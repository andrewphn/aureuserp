<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Google Drive integration columns to CNC tables for linking
     * VCarve files, toolpaths, and reference photos to Drive folders.
     */
    public function up(): void
    {
        // Add Google Drive columns to CNC Programs
        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            $table->string('vcarve_drive_id')->nullable()->after('vcarve_file')
                ->comment('Google Drive file ID for VCarve .crv file');
            $table->string('vcarve_drive_url')->nullable()->after('vcarve_drive_id')
                ->comment('Google Drive URL for VCarve file');
            $table->string('toolpath_folder_id')->nullable()->after('vcarve_drive_url')
                ->comment('Google Drive folder ID for ToolPaths');
            $table->string('reference_folder_id')->nullable()->after('toolpath_folder_id')
                ->comment('Google Drive folder ID for Reference Photos');
        });

        // Add Google Drive columns to CNC Program Parts
        Schema::table('projects_cnc_program_parts', function (Blueprint $table) {
            $table->string('nc_drive_id')->nullable()->after('file_path')
                ->comment('Google Drive file ID for NC/G-code file');
            $table->string('nc_drive_url')->nullable()->after('nc_drive_id')
                ->comment('Google Drive URL for NC file');
            $table->string('reference_photo_drive_id')->nullable()->after('nc_drive_url')
                ->comment('Google Drive file ID for reference photo');
            $table->string('reference_photo_url')->nullable()->after('reference_photo_drive_id')
                ->comment('Google Drive URL for reference photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            $table->dropColumn([
                'vcarve_drive_id',
                'vcarve_drive_url',
                'toolpath_folder_id',
                'reference_folder_id',
            ]);
        });

        Schema::table('projects_cnc_program_parts', function (Blueprint $table) {
            $table->dropColumn([
                'nc_drive_id',
                'nc_drive_url',
                'reference_photo_drive_id',
                'reference_photo_url',
            ]);
        });
    }
};
