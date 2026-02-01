<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Gate requirements define the individual conditions that must be met for a gate to pass.
     * These are data-driven and can be configured without code changes.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_gates')) {
            return;
        }

Schema::create('projects_gate_requirements', function (Blueprint $table) {
            $table->id();
            
            // Parent gate
            $table->foreignId('gate_id')
                ->constrained('projects_gates')
                ->cascadeOnDelete();
            
            // Requirement type - determines how the requirement is evaluated
            $table->enum('requirement_type', [
                'field_not_null',       // Check if field has a non-null value
                'field_equals',         // Field equals specific value
                'field_greater_than',   // Field is greater than value
                'relation_exists',      // Has at least one related record
                'relation_count',       // Has minimum count of related records
                'all_children_pass',    // All child entities pass a condition
                'document_uploaded',    // Specific document type exists
                'payment_received',     // Payment milestone has been met
                'task_completed',       // Specific task type is completed
                'custom_check',         // Custom PHP class/method check
            ]);
            
            // Target configuration
            $table->string('target_model', 100)->nullable();    // 'Project', 'SalesOrder', 'Cabinet'
            $table->string('target_relation', 100)->nullable(); // 'orders', 'cabinets', 'bomLines'
            $table->string('target_field', 100)->nullable();    // 'deposit_paid_at', 'qc_passed'
            $table->text('target_value')->nullable();           // Expected value (JSON for complex)
            $table->string('comparison_operator', 20)->default('!='); // '!=', '=', '>=', '>', '<', '<='
            
            // For custom_check type
            $table->string('custom_check_class', 255)->nullable();  // Full class path
            $table->string('custom_check_method', 100)->nullable(); // Method to call
            
            // User-facing message when requirement fails
            $table->string('error_message', 255);
            $table->string('help_text', 500)->nullable();       // Additional guidance
            $table->string('action_label', 100)->nullable();    // "Request Approval", "Upload Document"
            $table->string('action_route', 255)->nullable();    // Route to resolve the blocker
            
            // Ordering
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('gate_id');
            $table->index(['gate_id', 'sequence']);
            $table->index('requirement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_gate_requirements');
    }
};
