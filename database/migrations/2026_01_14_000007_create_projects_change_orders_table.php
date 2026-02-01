<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Change orders track scope changes to locked projects.
     * They are the only legal path to modify locked data.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_projects')) {
            return;
        }

Schema::create('projects_change_orders', function (Blueprint $table) {
            $table->id();
            
            // Project reference
            $table->foreignId('project_id')
                ->constrained('projects_projects')
                ->cascadeOnDelete();
            
            // Change order identification
            $table->string('change_order_number', 50);  // "CO-001", "CO-002"
            $table->string('title', 255);
            $table->text('description')->nullable();
            
            // Reason categorization
            $table->enum('reason', [
                'client_request',
                'field_condition',
                'design_error',
                'material_substitution',
                'scope_addition',
                'scope_removal',
                'other',
            ])->default('client_request');
            
            // Workflow status
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'rejected',
                'applied',
                'cancelled',
            ])->default('draft');
            
            // Request tracking
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            
            // Approval tracking
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Rejection tracking
            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Application tracking
            $table->foreignId('applied_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            
            // Financial impact
            $table->decimal('price_delta', 12, 2)->default(0);
            $table->decimal('labor_hours_delta', 8, 2)->default(0);
            
            // BOM impact (snapshot of changes)
            $table->json('bom_delta_json')->nullable();
            
            // Stage impact
            $table->string('affected_stage', 50)->nullable();
            $table->string('unlocks_gate', 50)->nullable();
            
            // Link to sales order (for billing the change)
            $table->foreignId('sales_order_id')
                ->nullable()
                ->constrained('sales_orders')
                ->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index('project_id');
            $table->index('status');
            $table->index('change_order_number');
            $table->index(['project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_change_orders');
    }
};
