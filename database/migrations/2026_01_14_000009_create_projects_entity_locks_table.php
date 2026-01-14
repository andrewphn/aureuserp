<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Entity locks track which project entities are locked from editing.
     * Locks can be at different levels (full, dimensions, materials, pricing)
     * and can be temporarily unlocked via change orders.
     */
    public function up(): void
    {
        Schema::create('projects_entity_locks', function (Blueprint $table) {
            $table->id();
            
            // Project scope
            $table->foreignId('project_id')
                ->constrained('projects_projects')
                ->cascadeOnDelete();
            
            // Entity identification
            $table->string('entity_type', 100);     // 'Cabinet', 'CabinetSection', 'Door', 'Drawer', etc.
            $table->unsignedBigInteger('entity_id')->nullable();  // NULL = all entities of type
            
            // Lock configuration
            $table->enum('lock_level', ['full', 'dimensions', 'materials', 'pricing']);
            $table->string('locked_by_gate', 50);   // Which gate triggered the lock
            
            // Lock metadata
            $table->timestamp('locked_at');
            $table->foreignId('locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Unlock tracking (via change order)
            $table->foreignId('unlock_change_order_id')
                ->nullable()
                ->constrained('projects_change_orders')
                ->nullOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index('project_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('locked_by_gate');
            $table->index('lock_level');
            
            // Unique constraint: one active lock per entity/level
            // (An entity can have multiple locks at different levels)
            $table->unique(['project_id', 'entity_type', 'entity_id', 'lock_level'], 'entity_lock_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_entity_locks');
    }
};
