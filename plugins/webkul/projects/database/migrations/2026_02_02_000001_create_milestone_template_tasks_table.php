<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects_milestone_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_template_id')
                ->constrained('projects_milestone_templates')
                ->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('projects_milestone_template_tasks')
                ->cascadeOnDelete();

            // Task definition
            $table->string('title');
            $table->text('description')->nullable();
            $table->float('allocated_hours')->default(0);
            $table->boolean('priority')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Relative timing (days from milestone deadline, negative = before)
            $table->integer('relative_days')->default(0)->comment('Days relative to milestone deadline (negative = before)');

            $table->timestamps();

            // Index for efficient queries (shortened names for MySQL compatibility)
            $table->index(['milestone_template_id', 'parent_id'], 'mtt_template_parent_idx');
            $table->index(['milestone_template_id', 'sort_order'], 'mtt_template_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_milestone_template_tasks');
    }
};
