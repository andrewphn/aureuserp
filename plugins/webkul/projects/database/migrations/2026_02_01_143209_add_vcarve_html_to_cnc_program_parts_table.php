<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds VCarve HTML storage to CNC Program Parts for visualization.
     * The HTML files contain embedded SVG with toolpath previews.
     */
    public function up(): void
    {
        Schema::table('projects_cnc_program_parts', function (Blueprint $table) {
            // Path to stored VCarve HTML file (for larger files)
            $table->string('vcarve_html_path')->nullable()->after('reference_photo_url')
                ->comment('Local storage path to VCarve HTML setup sheet');

            // Google Drive ID for VCarve HTML file
            $table->string('vcarve_html_drive_id')->nullable()->after('vcarve_html_path')
                ->comment('Google Drive file ID for VCarve HTML');

            // Google Drive URL for VCarve HTML file
            $table->string('vcarve_html_drive_url')->nullable()->after('vcarve_html_drive_id')
                ->comment('Google Drive URL for VCarve HTML');

            // Extracted SVG content for quick rendering (just the SVG, not full HTML)
            $table->longText('vcarve_svg_content')->nullable()->after('vcarve_html_drive_url')
                ->comment('Extracted SVG content from VCarve HTML for visualization');

            // Parsed metadata from VCarve file
            $table->json('vcarve_metadata')->nullable()->after('vcarve_svg_content')
                ->comment('Parsed metadata: material dimensions, toolpaths, etc.');
        });

        // Also add to CncProgram for program-level reference HTML
        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            // Reference HTML folder in Drive
            $table->string('reference_html_folder_id')->nullable()->after('reference_folder_id')
                ->comment('Google Drive folder ID for VCarve HTML setup sheets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cnc_program_parts', function (Blueprint $table) {
            $table->dropColumn([
                'vcarve_html_path',
                'vcarve_html_drive_id',
                'vcarve_html_drive_url',
                'vcarve_svg_content',
                'vcarve_metadata',
            ]);
        });

        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            $table->dropColumn('reference_html_folder_id');
        });
    }
};
