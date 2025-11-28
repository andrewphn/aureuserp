<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates pdf_annotation_history table with correct foreign key.
     * Points to pdf_page_annotations (the detailed annotation system).
     */
    public function up(): void
    {
        // Only create if not already exists
        if (Schema::hasTable('pdf_annotation_history')) {
            return;
        }

        Schema::create('pdf_annotation_history', function (Blueprint $table) {
            $table->id();

            // Reference to the annotation - Points to detailed system
            $table->foreignId('annotation_id')
                ->nullable()
                ->constrained('pdf_page_annotations')
                ->onDelete('cascade');

            // Which page it belongs to
            $table->foreignId('pdf_page_id')
                ->nullable()
                ->constrained('pdf_pages')
                ->onDelete('cascade');

            // Action type
            $table->enum('action', [
                'created', 'updated', 'deleted',
                'moved', 'resized', 'selected',
                'copied', 'pasted'
            ]);

            // User who performed the action
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Data snapshot (before and after)
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();

            // Additional context
            $table->json('metadata')->nullable(); // e.g., {shift_key: true, copy_count: 3}

            // IP and user agent for security audit
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            // Indexes for fast queries
            $table->index('annotation_id');
            $table->index('pdf_page_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_annotation_history');
    }
};
