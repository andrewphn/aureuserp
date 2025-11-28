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
     * Stores PDF annotations (boxes drawn on PDF pages) with hierarchical relationships.
     *
     * Workflow:
     * 1. User opens PDF page
     * 2. Draws box around "cabinet run" area -> Creates parent annotation
     * 3. Within that box, draws smaller boxes for cabinets -> Creates child annotations
     * 4. Links annotations to actual cabinet_run_id and cabinet_specification_id
     *
     * Hierarchy:
     * - Level 1: Cabinet Run box (parent_annotation_id = null, links to cabinet_run_id)
     * - Level 2: Individual Cabinet boxes (parent_annotation_id = run box, links to cabinet_specification_id)
     */
    public function up(): void
    {
        Schema::create('pdf_page_annotations', function (Blueprint $table) {
            $table->id();

            // PDF page relationship
            $table->foreignId('pdf_page_id')
                ->constrained('pdf_pages')
                ->onDelete('cascade')
                ->comment('Which PDF page this annotation is on');

            // Hierarchical structure
            $table->foreignId('parent_annotation_id')
                ->nullable()
                ->constrained('pdf_page_annotations')
                ->onDelete('cascade')
                ->comment('Parent annotation (null for top-level run boxes)');

            // Annotation type and label
            $table->string('annotation_type')
                ->comment('Type: cabinet_run, cabinet, note, etc.');

            $table->string('label')
                ->nullable()
                ->comment('User-provided label for the box');

            // Bounding box coordinates (relative to PDF page dimensions)
            $table->decimal('x', 10, 4)
                ->comment('X coordinate (left edge) in PDF units');

            $table->decimal('y', 10, 4)
                ->comment('Y coordinate (top edge) in PDF units');

            $table->decimal('width', 10, 4)
                ->comment('Width of the box in PDF units');

            $table->decimal('height', 10, 4)
                ->comment('Height of the box in PDF units');

            // Relationships to actual data
            $table->foreignId('cabinet_run_id')
                ->nullable()
                ->constrained('projects_cabinet_runs')
                ->onDelete('set null')
                ->comment('Link to actual cabinet run record');

            $table->foreignId('cabinet_specification_id')
                ->nullable()
                ->constrained('projects_cabinet_specifications')
                ->onDelete('set null')
                ->comment('Link to actual cabinet specification record');

            // Visual properties (stored from Nutrient annotation)
            $table->json('visual_properties')
                ->nullable()
                ->comment('Color, stroke width, style, etc.');

            // Nutrient SDK data
            $table->text('nutrient_annotation_id')
                ->nullable()
                ->comment('Nutrient SDK annotation ID for syncing');

            $table->json('nutrient_data')
                ->nullable()
                ->comment('Full Nutrient annotation data in Instant JSON format');

            // General notes
            $table->text('notes')
                ->nullable()
                ->comment('User notes about this annotation');

            // Metadata
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('pdf_page_id');
            $table->index('parent_annotation_id');
            $table->index('annotation_type');
            $table->index('cabinet_run_id');
            $table->index('cabinet_specification_id');
            $table->index(['pdf_page_id', 'annotation_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_page_annotations');
    }
};
