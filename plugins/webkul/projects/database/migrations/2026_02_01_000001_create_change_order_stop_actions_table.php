<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the change_order_stop_actions table for tracking
     * all stop actions taken when change orders are approved.
     * This provides a complete audit trail of blocked tasks,
     * held purchase orders, and notifications sent.
     */
    public function up(): void
    {
        Schema::create('projects_change_order_stop_actions', function (Blueprint $table) {
            $table->id();

            // Reference to the change order that triggered this action
            $table->foreignId('change_order_id')
                ->constrained('projects_change_orders')
                ->cascadeOnDelete();

            // Action type: task_blocked, po_held, delivery_blocked, notification_sent
            $table->string('action_type', 50);

            // The entity that was affected (Task, PurchaseOrder, Project)
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');

            // State tracking for reversal
            $table->string('previous_state', 50)->nullable();
            $table->string('new_state', 50)->nullable();

            // Audit fields for when action was performed
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('performed_at')->nullable();

            // Audit fields for when action was reverted
            $table->foreignId('reverted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reverted_at')->nullable();

            // Additional metadata (e.g., notification details)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['change_order_id', 'action_type']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('performed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_change_order_stop_actions');
    }
};
