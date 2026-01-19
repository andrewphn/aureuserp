<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create tables for Rhino extraction pipeline:
     * - rhino_extraction_jobs: Track async extraction job status
     * - rhino_extraction_reviews: Review queue for low-confidence extractions
     */
    public function up(): void
    {
        // Extraction jobs table - tracks async extraction job status
        Schema::create('rhino_extraction_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relationships
            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects_projects')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Job configuration
            $table->string('status', 50)->default('pending'); // pending, processing, completed, failed
            $table->json('options')->nullable(); // Extraction options (force, include_fixtures, etc.)

            // Rhino document info
            $table->string('rhino_document_name')->nullable();
            $table->string('rhino_document_path')->nullable();
            $table->json('rhino_metadata')->nullable(); // Document layers, groups, object counts

            // Results
            $table->json('results')->nullable(); // Extracted cabinet data
            $table->integer('cabinets_extracted')->default(0);
            $table->integer('cabinets_imported')->default(0);
            $table->integer('cabinets_pending_review')->default(0);

            // Error tracking
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->integer('retry_count')->default(0);

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index(['project_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        // Extraction reviews table - review queue for low-confidence extractions
        Schema::create('rhino_extraction_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relationships
            $table->foreignId('extraction_job_id')
                ->constrained('rhino_extraction_jobs')
                ->cascadeOnDelete();
            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects_projects')
                ->nullOnDelete();
            $table->foreignId('cabinet_id')
                ->nullable()
                ->constrained('projects_cabinets')
                ->nullOnDelete();

            // Rhino source info
            $table->string('rhino_group_name')->nullable();
            $table->string('cabinet_number')->nullable();

            // Extraction data
            $table->json('extraction_data'); // Raw extracted dimensions, components
            $table->json('ai_interpretation')->nullable(); // AI-suggested corrections
            $table->decimal('confidence_score', 5, 2)->default(0); // 0-100

            // Review status
            $table->string('status', 50)->default('pending'); // pending, approved, rejected, auto_approved
            $table->string('review_type', 50)->default('low_confidence'); // low_confidence, dimension_mismatch, sync_conflict

            // Reviewer info
            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('reviewer_corrections')->nullable(); // Changes made during review
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            // For sync conflicts
            $table->json('erp_data')->nullable(); // Current ERP cabinet data
            $table->json('rhino_data')->nullable(); // Incoming Rhino data
            $table->string('sync_direction')->nullable(); // push, pull, conflict

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('confidence_score');
            $table->index(['extraction_job_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index('review_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rhino_extraction_reviews');
        Schema::dropIfExists('rhino_extraction_jobs');
    }
};
