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
     * Adds support for multiple view types (plan, elevation, section, detail)
     * to PDF page annotations for better organization of cabinet drawings.
     *
     * Use Cases:
     * - Plan view: Top-down layout (existing functionality)
     * - Elevation view: Front/back/left/right views of cabinet runs
     * - Section view: Cut-through views showing internal structure
     * - Detail view: Zoomed callout regions with higher scale
     *
     * NOTE: Originally included annotation_entity_references table for multi-parent support,
     * but that feature was removed as unused (Oct 24, 2025).
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
