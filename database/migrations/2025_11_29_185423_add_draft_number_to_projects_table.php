<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds draft_number column to store the draft identifier separately from project_number.
     *
     * Workflow:
     * 1. New project created → Gets draft_number (e.g., TCS-D047-123MainSt)
     * 2. Project converted to official → Gets project_number (e.g., TCS-501-123MainSt)
     *
     * This enables:
     * - Tracking total inquiries/quotes (draft count)
     * - Tracking actual jobs (project count)
     * - Calculating conversion rates
     */
    public function up(): void
    {
        // Skip if projects_projects table doesn't exist (plugin not installed)
        if (!Schema::hasTable('projects_projects')) {
            return;
        }

        Schema::table('projects_projects', function (Blueprint $table) {
            $table->string('draft_number', 255)
                ->nullable()
                ->after('project_number')
                ->comment('Draft identifier (e.g., TCS-D047-123MainSt) - assigned at creation');

            $table->boolean('is_converted')
                ->default(false)
                ->after('is_active')
                ->comment('Whether draft has been converted to official project');

            $table->timestamp('converted_at')
                ->nullable()
                ->after('is_converted')
                ->comment('When the draft was converted to official project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn(['draft_number', 'is_converted', 'converted_at']);
        });
    }
};
