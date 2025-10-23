<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds support for multiple view types (plan, elevation, section, detail)
     * and multi-parent entity references for annotations.
     *
     * Use Cases:
     * - Plan view: Top-down layout (existing functionality)
     * - Elevation view: Front/back/left/right views of cabinet runs
     * - Section view: Cut-through views showing internal structure
     * - Detail view: Zoomed callout regions with higher scale
     *
     * Multi-parent support allows annotations to reference multiple entities.
     * Example: End panel annotation can reference both cabinet AND cabinet_run
     */
    public function up(): void
    {
        // Add view type columns to existing annotations table
        Schema::table('pdf_page_annotations', function (Blueprint $table) {
            // View classification
            $table->enum('view_type', ['plan', 'elevation', 'section', 'detail'])
                ->default('plan')
                ->after('annotation_type')
                ->comment('Type of view: plan (top-down), elevation (side), section (cut), detail (zoom)');

            $table->string('view_orientation', 20)
                ->nullable()
                ->after('view_type')
                ->comment('Orientation: front, back, left, right, top, A-A, etc.');

            $table->decimal('view_scale', 8, 4)
                ->nullable()
                ->after('view_orientation')
                ->comment('Scale factor for detail views (e.g., 2.0 = 2x zoom)');

            // Auto-detected positioning (inferred from Y coordinate)
            $table->string('inferred_position', 50)
                ->nullable()
                ->after('view_scale')
                ->comment('Auto-detected: upper, base, tall, wall_cabinet, etc.');

            $table->enum('vertical_zone', ['upper', 'middle', 'lower'])
                ->nullable()
                ->after('inferred_position')
                ->comment('Vertical zone on page: upper (<30%), middle (30-70%), lower (>70%)');

            // Add indexes for view filtering queries
            $table->index(['view_type', 'view_orientation']);
            $table->index('inferred_position');
        });

        // Create pivot table for multi-parent entity references
        Schema::create('annotation_entity_references', function (Blueprint $table) {
            $table->id();

            // Annotation being referenced
            $table->foreignId('annotation_id')
                ->constrained('pdf_page_annotations')
                ->onDelete('cascade')
                ->comment('The annotation that references entities');

            // Polymorphic entity reference
            $table->enum('entity_type', ['room', 'location', 'cabinet_run', 'cabinet'])
                ->comment('Type of entity being referenced');

            $table->unsignedBigInteger('entity_id')
                ->comment('ID of the referenced entity (polymorphic)');

            // Reference classification
            $table->enum('reference_type', ['primary', 'secondary', 'context'])
                ->default('primary')
                ->comment('primary=main entity, secondary=related entity, context=background info');

            // Metadata
            $table->timestamps();

            // Indexes for efficient queries
            $table->index('annotation_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('reference_type');
            $table->unique(['annotation_id', 'entity_type', 'entity_id'], 'unique_annotation_entity_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop pivot table first (foreign key constraint)
        Schema::dropIfExists('annotation_entity_references');

        // Remove columns from annotations table
        Schema::table('pdf_page_annotations', function (Blueprint $table) {
            $table->dropIndex(['view_type', 'view_orientation']);
            $table->dropIndex(['inferred_position']);
            $table->dropColumn([
                'view_type',
                'view_orientation',
                'view_scale',
                'inferred_position',
                'vertical_zone'
            ]);
        });
    }
};
