<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects_milestone_template_tasks', function (Blueprint $table) {
            $table->integer('duration_days')->default(1)->after('relative_days')
                ->comment('Duration of task in days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_milestone_template_tasks', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
