<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Gate evaluations provide an audit log of every time a gate was checked.
     * This enables "why is this blocked?" queries and compliance auditing.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_projects')) {
            return;
        }

Schema::create('projects_gate_evaluations', function (Blueprint $table) {
            $table->id();
            
            // Links
            $table->foreignId('project_id')
                ->constrained('projects_projects')
                ->cascadeOnDelete();
            $table->foreignId('gate_id')
                ->constrained('projects_gates')
                ->cascadeOnDelete();
            
            // Evaluation result
            $table->boolean('passed');
            $table->timestamp('evaluated_at');
            $table->foreignId('evaluated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Detailed results - JSON for flexibility
            $table->json('requirement_results')->nullable();    // Per-requirement pass/fail with details
            $table->json('failure_reasons')->nullable();        // Array of failed requirement messages
            
            // Context snapshot - captures relevant data at evaluation time
            $table->json('context')->nullable();                // Project state, counts, key values
            
            // Evaluation metadata
            $table->enum('evaluation_type', [
                'manual',       // User clicked "Check Gate" or "Advance"
                'automatic',    // System checked (e.g., on data change)
                'scheduled',    // Periodic check (if implemented)
            ])->default('manual');
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('project_id');
            $table->index('gate_id');
            $table->index('evaluated_at');
            $table->index('passed');
            $table->index(['project_id', 'gate_id', 'evaluated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_gate_evaluations');
    }
};
