<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add selected_milestone_templates field to projects table.
 *
 * This allows users to choose which milestone templates to include
 * when creating a project, rather than all-or-nothing via allow_milestones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            // JSON array of milestone template IDs to create for this project
            // null = use all active templates (default behavior)
            // empty array = no milestones
            // [1,2,5] = only create milestones from templates with these IDs
            $table->json('selected_milestone_templates')->nullable()->after('allow_milestones');
        });
    }

    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn('selected_milestone_templates');
        });
    }
};
