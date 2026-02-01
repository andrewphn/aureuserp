<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields to track when a task is blocked due to a change order.
     * When a change order is approved, related production tasks are blocked.
     * These fields allow us to restore the previous state when the
     * change order is applied or cancelled.
     */
    public function up(): void
    {
        Schema::table('projects_tasks', function (Blueprint $table) {
            // Reference to the change order blocking this task
            $table->foreignId('blocked_by_change_order_id')
                ->nullable()
                ->after('state')
                ->constrained('projects_change_orders')
                ->nullOnDelete();

            // The state before the task was blocked (for restoration)
            $table->string('state_before_block', 50)->nullable()->after('blocked_by_change_order_id');

            // Index for efficient querying of blocked tasks
            $table->index('blocked_by_change_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_tasks', function (Blueprint $table) {
            $table->dropForeign(['blocked_by_change_order_id']);
            $table->dropIndex(['blocked_by_change_order_id']);

            $table->dropColumn([
                'blocked_by_change_order_id',
                'state_before_block',
            ]);
        });
    }
};
