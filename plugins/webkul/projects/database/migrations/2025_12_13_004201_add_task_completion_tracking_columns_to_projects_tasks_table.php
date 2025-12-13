<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds timestamps for tracking task lifecycle:
     * - started_at: When task was first moved to "in_progress"
     * - completed_at: When task was marked "done"
     * - started_by/completed_by: Who made those changes
     *
     * This enables productivity tracking: correlating tasks completed
     * during an employee's clock-in session.
     */
    public function up(): void
    {
        Schema::table('projects_tasks', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('state');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->foreignId('started_by')->nullable()->after('completed_at')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->after('started_by')
                ->constrained('users')->nullOnDelete();

            // Index for productivity queries (tasks completed by user in time range)
            $table->index(['completed_by', 'completed_at']);
            $table->index(['started_by', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_tasks', function (Blueprint $table) {
            $table->dropIndex(['completed_by', 'completed_at']);
            $table->dropIndex(['started_by', 'started_at']);
            $table->dropForeign(['started_by']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['started_at', 'completed_at', 'started_by', 'completed_by']);
        });
    }
};
