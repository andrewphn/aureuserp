<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Gates define checkpoints that must pass before a project can advance to the next stage.
     * Each stage can have multiple gates, evaluated in sequence order.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_stages')) {
            return;
        }

Schema::create('projects_gates', function (Blueprint $table) {
            $table->id();
            
            // Link to stage - a gate belongs to a specific project stage
            $table->foreignId('stage_id')
                ->constrained('projects_project_stages')
                ->cascadeOnDelete();
            
            // Gate identification
            $table->string('name', 100);                    // "Discovery Complete", "Design Lock"
            $table->string('gate_key', 50)->unique();       // "discovery_complete", "design_lock"
            $table->text('description')->nullable();
            
            // Ordering and behavior
            $table->unsignedInteger('sequence')->default(0);        // Order within stage
            $table->boolean('is_blocking')->default(true);          // Blocks stage advancement
            $table->boolean('is_active')->default(true);            // Can be disabled without deletion
            
            // Lock configuration - what gets locked when this gate passes
            $table->boolean('applies_design_lock')->default(false);
            $table->boolean('applies_procurement_lock')->default(false);
            $table->boolean('applies_production_lock')->default(false);
            
            // Automation triggers
            $table->boolean('creates_tasks_on_pass')->default(false);
            $table->json('task_templates_json')->nullable();        // Task templates to create
            
            $table->timestamps();
            
            // Indexes
            $table->index('stage_id');
            $table->index(['stage_id', 'sequence']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_gates');
    }
};
