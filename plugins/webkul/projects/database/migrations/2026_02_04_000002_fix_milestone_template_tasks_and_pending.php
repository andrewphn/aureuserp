<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix migration: Creates milestone_template_tasks table and applies all pending
 * column additions that failed due to duplicate timestamp conflict.
 *
 * This migration consolidates:
 * - 2026_02_02_000001_create_milestone_template_tasks_table (skipped due to duplicate timestamp)
 * - 2026_02_02_000002_add_duration_days_to_milestone_template_tasks
 * - 2026_02_02_000003_add_duration_formula_to_milestone_template_tasks
 * - 2026_02_02_000004_add_duration_rate_key_to_milestone_template_tasks
 * - 2026_02_03_000002_add_ai_suggestion_id_to_milestone_template_tasks
 * - 2026_02_03_000003_create_milestone_template_tag_table
 * - 2026_02_03_100001_create_production_metrics_daily_table
 *
 * All operations use Schema::hasTable/hasColumn checks for idempotency.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Create milestone_template_tasks table if it doesn't exist
        if (! Schema::hasTable('projects_milestone_template_tasks')) {
            Schema::create('projects_milestone_template_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('milestone_template_id')
                    ->constrained('projects_milestone_templates')
                    ->cascadeOnDelete();
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('projects_milestone_template_tasks')
                    ->cascadeOnDelete();

                $table->string('title');
                $table->text('description')->nullable();
                $table->float('allocated_hours')->default(0);
                $table->boolean('priority')->default(false);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->integer('relative_days')->default(0)->comment('Days relative to milestone deadline');

                // Include all column additions from subsequent migrations
                $table->integer('duration_days')->default(1)->comment('Duration of task in days');
                $table->string('duration_type', 20)->default('fixed')->comment('fixed or formula');
                $table->decimal('duration_per_unit', 8, 2)->nullable();
                $table->decimal('duration_unit_size', 10, 2)->nullable();
                $table->string('duration_unit_type', 50)->nullable();
                $table->integer('duration_min_days')->nullable();
                $table->integer('duration_max_days')->nullable();
                $table->string('duration_rate_key', 50)->nullable();

                // AI suggestion link
                $table->foreignId('ai_suggestion_id')
                    ->nullable()
                    ->constrained('projects_ai_task_suggestions')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index(['milestone_template_id', 'parent_id'], 'mtt_template_parent_idx');
                $table->index(['milestone_template_id', 'sort_order'], 'mtt_template_sort_idx');
            });
        } else {
            // Table exists (local dev), just add any missing columns
            Schema::table('projects_milestone_template_tasks', function (Blueprint $table) {
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'duration_days')) {
                    $table->integer('duration_days')->default(1)->after('relative_days');
                }
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'duration_type')) {
                    $table->string('duration_type', 20)->default('fixed')->after('duration_days');
                }
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'duration_per_unit')) {
                    $table->decimal('duration_per_unit', 8, 2)->nullable()->after('duration_type');
                }
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'duration_unit_size')) {
                    $table->decimal('duration_unit_size', 10, 2)->nullable()->after('duration_per_unit');
                }
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'duration_unit_type')) {
                    $table->string('duration_unit_type', 50)->nullable()->after('duration_unit_size');
                }
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'duration_min_days')) {
                    $table->integer('duration_min_days')->nullable()->after('duration_unit_type');
                }
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'duration_max_days')) {
                    $table->integer('duration_max_days')->nullable()->after('duration_min_days');
                }
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'duration_rate_key')) {
                    $table->string('duration_rate_key', 50)->nullable()->after('duration_max_days');
                }
                if (! Schema::hasColumn('projects_milestone_template_tasks', 'ai_suggestion_id')) {
                    $table->foreignId('ai_suggestion_id')
                        ->nullable()
                        ->after('milestone_template_id')
                        ->constrained('projects_ai_task_suggestions')
                        ->nullOnDelete();
                }
            });
        }

        // 2. Create milestone_template_tag table if it doesn't exist
        if (! Schema::hasTable('projects_milestone_template_tag')) {
            Schema::create('projects_milestone_template_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('milestone_template_id')
                    ->constrained('projects_milestone_templates')
                    ->cascadeOnDelete();
                $table->foreignId('tag_id')
                    ->constrained('projects_tags')
                    ->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['milestone_template_id', 'tag_id'], 'milestone_template_tag_unique');
            });
        }

        // 3. Create production_metrics_daily table if it doesn't exist
        if (! Schema::hasTable('projects_production_metrics_daily')) {
            Schema::create('projects_production_metrics_daily', function (Blueprint $table) {
                $table->id();
                $table->date('metric_date')->unique();
                $table->integer('sheets_cut')->default(0);
                $table->decimal('board_feet', 10, 2)->default(0);
                $table->decimal('square_feet', 10, 2)->default(0);
                $table->integer('programs_run')->default(0);
                $table->integer('projects_active')->default(0);
                $table->json('material_breakdown')->nullable();
                $table->json('project_breakdown')->nullable();
                $table->decimal('avg_cycle_time_minutes', 8, 2)->nullable();
                $table->decimal('machine_utilization_pct', 5, 2)->nullable();
                $table->timestamps();
            });
        }

        // 4. Mark the skipped/failed migrations as ran so they don't try to run again
        $migrationsToMark = [
            '2026_02_02_000001_create_milestone_template_tasks_table',
            '2026_02_02_000002_add_duration_days_to_milestone_template_tasks',
            '2026_02_02_000003_add_duration_formula_to_milestone_template_tasks',
            '2026_02_02_000004_add_duration_rate_key_to_milestone_template_tasks',
            '2026_02_03_000002_add_ai_suggestion_id_to_milestone_template_tasks',
            '2026_02_03_000003_create_milestone_template_tag_table',
            '2026_02_03_100001_create_production_metrics_daily_table',
        ];

        $batch = \DB::table('migrations')->max('batch') ?: 1;

        foreach ($migrationsToMark as $migration) {
            $exists = \DB::table('migrations')->where('migration', $migration)->exists();
            if (! $exists) {
                \DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch'     => $batch,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects_milestone_template_tag');
        Schema::dropIfExists('projects_production_metrics_daily');
        Schema::dropIfExists('projects_milestone_template_tasks');
    }
};
