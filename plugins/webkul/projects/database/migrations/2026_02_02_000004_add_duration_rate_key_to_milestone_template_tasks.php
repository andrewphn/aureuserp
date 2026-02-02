<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds duration_rate_key column to link task templates to company production rates.
     * When set, the task will use the company's rate instead of the custom formula.
     */
    public function up(): void
    {
        Schema::table('projects_milestone_template_tasks', function (Blueprint $table) {
            $table->string('duration_rate_key', 50)->nullable()->after('duration_max_days')
                ->comment('Company column name for production rate (e.g., design_concepts_lf_per_day)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_milestone_template_tasks', function (Blueprint $table) {
            $table->dropColumn('duration_rate_key');
        });
    }
};
