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
     * Creates cabinet sections table for subdivisions within cabinets.
     * A cabinet can have multiple sections (drawer stack, door opening, open shelving, etc.)
     *
     * Meeting Reference: "How do I call those sections? Oh, they call sections" (01:30:40)
     *
     * Example:
     * - Cabinet B36 has 3 sections:
     *   1. Top Drawer Section (3 drawers)
     *   2. Door Opening (2 doors)
     *   3. Bottom Shelf Section (1 adjustable shelf)
     */
    public function up(): void
    {
        Schema::create('projects_cabinet_sections', function (Blueprint $table) {
            $table->id();

            // Parent Cabinet
            $table->foreignId('cabinet_specification_id')
                ->constrained('projects_cabinet_specifications')
                ->onDelete('cascade')
                ->comment('Parent cabinet');

            // Section Identification
            $table->integer('section_number')->default(1)
                ->comment('Section order within cabinet (1, 2, 3...)');

            $table->string('name')
                ->comment('Section name: "Top Drawer Stack", "Door Opening", "Open Shelving"');

            $table->string('section_type', 50)
                ->comment('Type: drawer_stack, door_opening, open_shelving, pullout_area, appliance');

            // Dimensions (position within cabinet)
            $table->decimal('width_inches', 8, 3)->nullable()
                ->comment('Section width in inches');

            $table->decimal('height_inches', 8, 3)->nullable()
                ->comment('Section height in inches');

            $table->decimal('position_from_left_inches', 8, 3)->nullable()
                ->comment('Horizontal position from left edge of cabinet');

            $table->decimal('position_from_bottom_inches', 8, 3)->nullable()
                ->comment('Vertical position from bottom of cabinet');

            // Component Count
            $table->integer('component_count')->default(0)
                ->comment('Number of doors/drawers/shelves in this section');

            // Face Frame Opening (for this section)
            $table->decimal('opening_width_inches', 8, 3)->nullable()
                ->comment('Face frame opening width for this section');

            $table->decimal('opening_height_inches', 8, 3)->nullable()
                ->comment('Face frame opening height for this section');

            // Metadata
            $table->text('notes')->nullable()
                ->comment('Section-specific notes and specifications');

            $table->integer('sort_order')->default(0)
                ->comment('Display order within cabinet (top to bottom, left to right)');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('cabinet_specification_id', 'idx_sections_cabinet');
            $table->index('section_type', 'idx_sections_type');
            $table->index(['cabinet_specification_id', 'sort_order'], 'idx_sections_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_cabinet_sections');
    }
};
