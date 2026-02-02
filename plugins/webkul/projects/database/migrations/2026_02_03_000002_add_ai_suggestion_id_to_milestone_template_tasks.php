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
            $table->foreignId('ai_suggestion_id')
                ->nullable()
                ->after('milestone_template_id')
                ->constrained('projects_ai_task_suggestions')
                ->nullOnDelete()
                ->comment('Track if task was created from AI suggestion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_milestone_template_tasks', function (Blueprint $table) {
            $table->dropForeign(['ai_suggestion_id']);
            $table->dropColumn('ai_suggestion_id');
        });
    }
};
