<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the document_scan_logs table for AI document scanning audit trail.
     * Tracks every scan attempt, confidence scores, matching results, and review status.
     */
    public function up(): void
    {
        Schema::create('document_scan_logs', function (Blueprint $table) {
            $table->id();

            // Link to inventory operation (nullable - scan may happen before operation is created)
            $table->foreignId('operation_id')
                ->nullable()
                ->constrained('inventories_operations')
                ->nullOnDelete();

            // Document metadata
            $table->string('document_type'); // invoice, packing_slip, product_label, quote
            $table->string('file_path')->nullable(); // Stored image/PDF path
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('file_size')->nullable(); // bytes

            // AI response data
            $table->json('raw_ai_response')->nullable(); // Full response from Gemini
            $table->json('extracted_data')->nullable(); // Parsed/structured data

            // Confidence scores (0.00 - 1.00)
            $table->decimal('overall_confidence', 3, 2)->nullable();
            $table->decimal('vendor_confidence', 3, 2)->nullable();
            $table->decimal('po_confidence', 3, 2)->nullable();

            // Matching results
            $table->boolean('vendor_matched')->default(false);
            $table->foreignId('matched_vendor_id')
                ->nullable()
                ->constrained('partners_partners')
                ->nullOnDelete();

            $table->boolean('po_matched')->default(false);
            $table->foreignId('matched_po_id')
                ->nullable()
                ->constrained('purchases_orders')
                ->nullOnDelete();

            // Line item stats
            $table->unsignedInteger('lines_total_count')->default(0);
            $table->unsignedInteger('lines_matched_count')->default(0);
            $table->unsignedInteger('lines_unmatched_count')->default(0);

            // Review workflow
            $table->string('status')->default('pending_review'); // pending_review, approved, rejected, auto_applied
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->datetime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Processing metadata
            $table->unsignedInteger('processing_time_ms')->nullable(); // How long the AI took
            $table->string('error_message')->nullable(); // If scan failed

            // Audit fields
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes for common queries
            $table->index('document_type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['vendor_matched', 'po_matched']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_scan_logs');
    }
};
