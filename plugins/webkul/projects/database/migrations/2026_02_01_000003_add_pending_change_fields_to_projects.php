<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields to track when a project has a pending change order
     * and its delivery schedule is blocked. This provides quick access
     * to project status without needing to query change orders.
     */
    public function up(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            // Flag indicating if project has an active (approved but not applied) change order
            $table->boolean('has_pending_change_order')
                ->default(false)
                ->after('pricing_snapshot_json');

            // Reference to the active change order (if any)
            $table->foreignId('active_change_order_id')
                ->nullable()
                ->after('has_pending_change_order')
                ->constrained('projects_change_orders')
                ->nullOnDelete();

            // Flag indicating if delivery schedule is blocked due to change order
            $table->boolean('delivery_blocked')
                ->default(false)
                ->after('active_change_order_id');

            // Indexes for efficient querying
            $table->index('has_pending_change_order');
            $table->index('delivery_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropForeign(['active_change_order_id']);
            $table->dropIndex(['has_pending_change_order']);
            $table->dropIndex(['delivery_blocked']);

            $table->dropColumn([
                'has_pending_change_order',
                'active_change_order_id',
                'delivery_blocked',
            ]);
        });
    }
};
