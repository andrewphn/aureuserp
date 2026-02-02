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
        Schema::create('projects_ai_task_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_template_id')
                ->constrained('projects_milestone_templates')
                ->cascadeOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // AI-generated content (JSON array of suggested tasks)
            $table->json('suggested_tasks');

            // Review workflow status
            $table->string('status', 20)->default('pending')
                ->comment('pending, approved, rejected, partial');
            $table->decimal('confidence_score', 5, 2)->nullable()
                ->comment('AI confidence score 0-100');
            $table->text('ai_reasoning')->nullable()
                ->comment('AI explanation of suggestions');

            // Reviewer feedback
            $table->json('reviewer_corrections')->nullable()
                ->comment('Corrections made during review');
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            // Context used for generation
            $table->text('prompt_context')->nullable()
                ->comment('Context data sent to AI');
            $table->string('model_used', 50)->nullable()
                ->comment('AI model identifier');

            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['milestone_template_id', 'status'], 'ai_sugg_template_status_idx');
            $table->index(['status', 'created_at'], 'ai_sugg_status_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_ai_task_suggestions');
    }
};
