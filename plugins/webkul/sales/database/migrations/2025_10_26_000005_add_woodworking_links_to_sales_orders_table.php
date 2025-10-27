<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Link sales orders to woodworking projects for invoicing/quoting
     * Enables automatic generation of sales orders from project estimates
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            // Project Linkage (already exists but may need index)
            // Note: project_id column already added in previous migration
            // $table->foreignId('project_id')->nullable()->constrained('projects_projects');

            // Room-level linking (for room-specific invoices/quotes)
            $table->foreignId('room_id')->nullable()
                ->comment('Link to specific room if invoice is room-specific');
            $table->foreign('room_id')
                ->references('id')
                ->on('projects_rooms')
                ->onDelete('set null');

            // Woodworking-specific order types
            $table->string('woodworking_order_type', 50)->nullable()
                ->comment('deposit, progress_payment, final_payment, change_order, full_project');

            // Payment Schedule (for deposit/balance split)
            $table->decimal('deposit_percentage', 5, 2)->nullable()
                ->comment('Deposit % (typically 30%)');
            $table->decimal('deposit_amount', 10, 2)->nullable()
                ->comment('Calculated deposit amount');
            $table->decimal('balance_percentage', 5, 2)->nullable()
                ->comment('Balance % (typically 70%)');
            $table->decimal('balance_amount', 10, 2)->nullable()
                ->comment('Calculated balance amount');
            $table->string('payment_terms', 100)->nullable()
                ->comment('e.g., "NET 5 Days deposit, NET 15 upon completion"');

            // Project Estimate Reference
            $table->decimal('project_estimated_value', 10, 2)->nullable()
                ->comment('Total project estimate from rooms');
            $table->decimal('quoted_price_override', 10, 2)->nullable()
                ->comment('Manual quote override (negotiated price)');
            $table->text('pricing_notes')->nullable()
                ->comment('Special pricing considerations, discounts applied');

            // Proposal/Quote Status
            $table->string('proposal_status', 50)->default('draft')
                ->comment('draft, sent, viewed, accepted, rejected');
            $table->timestamp('proposal_sent_at')->nullable()
                ->comment('When proposal was sent to client');
            $table->timestamp('proposal_viewed_at')->nullable()
                ->comment('When client viewed proposal');
            $table->timestamp('proposal_accepted_at')->nullable()
                ->comment('When client accepted proposal');
            $table->foreignId('proposal_sent_by_user_id')->nullable()->constrained('users')
                ->comment('User who sent proposal (Bryan, designer)');

            // Production Trigger
            $table->boolean('production_authorized')->default(false)
                ->comment('Deposit received, production can start');
            $table->timestamp('production_authorized_at')->nullable()
                ->comment('When production was authorized to begin');

            // Document Template Reference
            $table->string('invoice_template', 100)->nullable()
                ->comment('Template file: watchtower-invoice-30percent, etc.');
            $table->string('proposal_template', 100)->nullable()
                ->comment('Proposal template if different from invoice');

            // Change Order Tracking
            $table->boolean('is_change_order')->default(false)
                ->comment('This is a change order vs original scope');
            $table->foreignId('original_order_id')->nullable()
                ->comment('Reference to original order if change order');
            $table->foreign('original_order_id')
                ->references('id')
                ->on('sales_orders')
                ->onDelete('set null');
            $table->text('change_order_description')->nullable()
                ->comment('What changed from original scope');

            // Client Communication
            $table->text('client_notes')->nullable()
                ->comment('Notes visible to client on invoice/proposal');
            $table->text('internal_notes')->nullable()
                ->comment('Internal notes not visible to client');

            // Indexes for common queries
            $table->index(['project_id', 'room_id'], 'idx_order_project_room');
            $table->index(['proposal_status', 'proposal_sent_at'], 'idx_order_proposal');
            $table->index(['production_authorized', 'production_authorized_at'], 'idx_order_production');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex('idx_order_project_room');
            $table->dropIndex('idx_order_proposal');
            $table->dropIndex('idx_order_production');

            $table->dropForeign(['room_id']);
            $table->dropForeign(['proposal_sent_by_user_id']);
            $table->dropForeign(['original_order_id']);

            $table->dropColumn([
                'room_id',
                'woodworking_order_type',
                'deposit_percentage',
                'deposit_amount',
                'balance_percentage',
                'balance_amount',
                'payment_terms',
                'project_estimated_value',
                'quoted_price_override',
                'pricing_notes',
                'proposal_status',
                'proposal_sent_at',
                'proposal_viewed_at',
                'proposal_accepted_at',
                'proposal_sent_by_user_id',
                'production_authorized',
                'production_authorized_at',
                'invoice_template',
                'proposal_template',
                'is_change_order',
                'original_order_id',
                'change_order_description',
                'client_notes',
                'internal_notes',
            ]);
        });
    }
};
