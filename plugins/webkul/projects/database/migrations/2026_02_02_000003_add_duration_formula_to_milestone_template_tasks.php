<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds formula-based duration calculation support.
     * Example: "1 day per 15 LF" = duration_per_unit=1, duration_unit_size=15, duration_unit_type='linear_feet'
     */
    public function up(): void
    {
        Schema::table('projects_milestone_template_tasks', function (Blueprint $table) {
            // Duration calculation type: 'fixed' (use duration_days) or 'formula' (calculate based on project)
            $table->string('duration_type', 20)->default('fixed')->after('duration_days')
                ->comment('fixed = use duration_days, formula = calculate from project size');

            // Formula: X days per Y units (e.g., 1 day per 15 linear feet)
            $table->decimal('duration_per_unit', 8, 2)->nullable()->after('duration_type')
                ->comment('Days per unit (e.g., 1 = 1 day per unit_size)');

            $table->decimal('duration_unit_size', 10, 2)->nullable()->after('duration_per_unit')
                ->comment('Unit size for calculation (e.g., 15 = per 15 linear feet)');

            $table->string('duration_unit_type', 50)->nullable()->after('duration_unit_size')
                ->comment('Unit type: linear_feet, cabinets, rooms, etc.');

            // Minimum and maximum duration bounds
            $table->integer('duration_min_days')->nullable()->after('duration_unit_type')
                ->comment('Minimum duration in days (floor)');

            $table->integer('duration_max_days')->nullable()->after('duration_min_days')
                ->comment('Maximum duration in days (ceiling)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_milestone_template_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'duration_type',
                'duration_per_unit',
                'duration_unit_size',
                'duration_unit_type',
                'duration_min_days',
                'duration_max_days',
            ]);
        });
    }
};
