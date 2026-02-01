<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stage transitions provide a complete audit trail of all project stage changes.
     * This enables timeline reconstruction, compliance reporting, and debugging.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_projects')) {
            return;
        }

Schema::create('projects_stage_transitions', function (Blueprint $table) {
            $table->id();
            
            // Project reference
            $table->foreignId('project_id')
                ->constrained('projects_projects')
                ->cascadeOnDelete();
            
            // Stage change
            $table->foreignId('from_stage_id')
                ->nullable()
                ->constrained('projects_project_stages')
                ->nullOnDelete();
            $table->foreignId('to_stage_id')
                ->constrained('projects_project_stages')
                ->cascadeOnDelete();
            
            // Gate that was passed (if any)
            $table->foreignId('gate_id')
                ->nullable()
                ->constrained('projects_gates')
                ->nullOnDelete();
            
            // Transition type
            $table->enum('transition_type', [
                'advance',      // Normal forward progression
                'rollback',     // Moving backward (uncommon)
                'force',        // Admin override without gate check
                'system',       // Automatic system transition
            ]);
            
            // Who and when
            $table->timestamp('transitioned_at');
            $table->foreignId('transitioned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Context
            $table->text('reason')->nullable();             // Why transition happened (esp. for force/rollback)
            $table->foreignId('gate_evaluation_id')
                ->nullable()
                ->constrained('projects_gate_evaluations')
                ->nullOnDelete();
            
            // Additional metadata
            $table->json('metadata')->nullable();           // Any additional context data
            
            // Duration tracking (time spent in previous stage)
            $table->unsignedInteger('duration_minutes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('project_id');
            $table->index('transitioned_at');
            $table->index('transition_type');
            $table->index(['project_id', 'transitioned_at']);
            $table->index(['from_stage_id', 'to_stage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_stage_transitions');
    }
};
