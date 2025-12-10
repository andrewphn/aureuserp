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
            if (!Schema::hasColumn('projects_projects', 'initial_consultation_date')) {
                $table->date('initial_consultation_date')->nullable()
                    ->comment('Date of initial site visit/consultation');
            }
            if (!Schema::hasColumn('projects_projects', 'initial_consultation_notes')) {
                $table->text('initial_consultation_notes')->nullable()
                    ->comment('Notes from initial consultation');
            }

            // Design stage
            if (!Schema::hasColumn('projects_projects', 'design_revision_number')) {
                $table->unsignedInteger('design_revision_number')->default(1)
                    ->comment('Current design revision number');
            }
            if (!Schema::hasColumn('projects_projects', 'design_notes')) {
                $table->text('design_notes')->nullable()
                    ->comment('Designer notes and annotations');
            }
        });

        // Cabinet Specifications table - Additional production tracking
        if (Schema::hasTable('projects_cabinet_specifications') && !Schema::hasColumn('projects_cabinet_specifications', 'doweled_at')) {
            Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
                $table->timestamp('doweled_at')->nullable()
                    ->comment('Doweling completed');
            });
        }
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
