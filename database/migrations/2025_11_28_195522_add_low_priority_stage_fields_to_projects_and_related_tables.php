<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * LOW PRIORITY Stage Fields for TCS Production Workflow:
     * - Discovery: initial_consultation_date, initial_consultation_notes
     * - Design: design_revision_number, design_notes
     * - Production: doweled_at
     */
    public function up(): void
    {
        // Skip if projects_projects table doesn't exist (plugin not installed)
        if (!Schema::hasTable('projects_projects')) {
            return;
        }

        // Projects table - LOW priority fields
        Schema::table('projects_projects', function (Blueprint $table) {
            // Discovery stage
            $table->date('initial_consultation_date')->nullable()->after('lead_source')
                ->comment('Date of initial site visit/consultation');
            $table->text('initial_consultation_notes')->nullable()->after('initial_consultation_date')
                ->comment('Notes from initial consultation');

            // Design stage
            $table->unsignedInteger('design_revision_number')->default(1)->after('rhino_file_path')
                ->comment('Current design revision number');
            $table->text('design_notes')->nullable()->after('design_revision_number')
                ->comment('Designer notes and annotations');
        });

        // Cabinet Specifications table - Additional production tracking
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->timestamp('doweled_at')->nullable()->after('pocket_holes_at')
                ->comment('Doweling completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn([
                'initial_consultation_date',
                'initial_consultation_notes',
                'design_revision_number',
                'design_notes',
            ]);
        });

        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->dropColumn('doweled_at');
        });
    }
};
