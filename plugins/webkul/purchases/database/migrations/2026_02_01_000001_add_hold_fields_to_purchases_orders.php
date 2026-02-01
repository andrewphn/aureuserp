<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields to track when a purchase order is held due to a change order.
     * When a change order is approved, related POs are put on hold.
     * These fields allow us to restore the previous state when the
     * change order is applied or cancelled.
     */
    public function up(): void
    {
        Schema::table('purchases_orders', function (Blueprint $table) {
            // Reference to the change order holding this PO
            $table->foreignId('held_by_change_order_id')
                ->nullable()
                ->after('state')
                ->constrained('projects_change_orders')
                ->nullOnDelete();

            // When the PO was put on hold
            $table->timestamp('held_at')->nullable()->after('held_by_change_order_id');

            // User who held the PO
            $table->foreignId('held_by')
                ->nullable()
                ->after('held_at')
                ->constrained('users')
                ->nullOnDelete();

            // The state before the PO was held (for restoration)
            $table->string('state_before_hold', 50)->nullable()->after('held_by');

            // Index for efficient querying of held POs
            $table->index('held_by_change_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases_orders', function (Blueprint $table) {
            $table->dropForeign(['held_by_change_order_id']);
            $table->dropForeign(['held_by']);
            $table->dropIndex(['held_by_change_order_id']);

            $table->dropColumn([
                'held_by_change_order_id',
                'held_at',
                'held_by',
                'state_before_hold',
            ]);
        });
    }
};
