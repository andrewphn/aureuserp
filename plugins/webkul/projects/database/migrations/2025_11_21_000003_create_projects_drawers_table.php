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
     * Creates drawers table for all drawer components (fronts + boxes).
     *
     * Meeting Reference: "It has to be at the component level." (01:20:30)
     *
     * Tracks both drawer front (visible face) and drawer box (internal construction)
     * as a single unit since they're always paired together.
     */
    public function up(): void
    {
        Schema::create('projects_drawers', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('cabinet_specification_id')
                ->constrained('projects_cabinet_specifications')
                ->onDelete('cascade')
                ->comment('Parent cabinet');

            $table->foreignId('section_id')
                ->nullable()
                ->constrained('projects_cabinet_sections')
                ->onDelete('set null')
                ->comment('Parent section (optional)');

            // ========================================
            // IDENTIFICATION
            // ========================================

            $table->integer('drawer_number')->default(1)
                ->comment('Drawer position (1, 2, 3...)');

            $table->string('drawer_name', 100)->nullable()
                ->comment('Drawer name: DR1, DR2, etc.');

            $table->string('drawer_position', 50)->nullable()
                ->comment('top, middle, bottom');

            $table->integer('sort_order')->default(0)
                ->comment('Display order within cabinet/section');

            // ========================================
            // DRAWER FRONT DIMENSIONS
            // ========================================

            $table->decimal('front_width_inches', 8, 3)
                ->comment('Drawer front width');

            $table->decimal('front_height_inches', 8, 3)
                ->comment('Drawer front face height');

            // ========================================
            // DRAWER FRONT CONSTRUCTION
            // ========================================

            $table->decimal('top_rail_width_inches', 5, 3)->nullable()
                ->comment('Top rail width');

            $table->decimal('bottom_rail_width_inches', 5, 3)->nullable()
                ->comment('Bottom rail width');

            $table->decimal('style_width_inches', 5, 3)->nullable()
                ->comment('Vertical style width (left/right)');

            $table->string('profile_type', 100)->nullable()
                ->comment('shaker, flat_panel, beaded, raised_panel');

            $table->string('fabrication_method', 50)->nullable()
                ->comment('cnc, five_piece_manual, slab');

            $table->decimal('front_thickness_inches', 5, 3)->nullable()
                ->comment('Drawer front thickness');

            // ========================================
            // DRAWER BOX DIMENSIONS
            // ========================================

            $table->decimal('box_width_inches', 8, 3)->nullable()
                ->comment('Internal drawer box width');

            $table->decimal('box_depth_inches', 8, 3)->nullable()
                ->comment('Internal drawer box depth');

            $table->decimal('box_height_inches', 8, 3)->nullable()
                ->comment('Internal drawer box height');

            // ========================================
            // DRAWER BOX CONSTRUCTION
            // ========================================

            $table->string('box_material', 100)->nullable()
                ->comment('Drawer box: maple, birch, baltic_birch');

            $table->decimal('box_thickness', 5, 3)->nullable()
                ->comment('Drawer side thickness (0.5" or 0.75")');

            $table->string('joinery_method', 50)->nullable()
                ->comment('dovetail, pocket_screw, dado');

            // ========================================
            // DRAWER SLIDES
            // ========================================

            $table->string('slide_type', 100)->nullable()
                ->comment('blum_tandem, blum_undermount, full_extension');

            $table->string('slide_model', 100)->nullable()
                ->comment('Specific slide model number');

            $table->decimal('slide_length_inches', 5, 2)->nullable()
                ->comment('18", 21", 24" typical');

            $table->integer('slide_quantity')->nullable()
                ->comment('Pairs of slides (usually 1)');

            $table->boolean('soft_close')->default(false)
                ->comment('Soft close slides');

            // ========================================
            // FINISH & APPEARANCE
            // ========================================

            $table->string('finish_type', 100)->nullable()
                ->comment('Inherits from cabinet if NULL: painted, stained, clear_coat');

            $table->string('paint_color', 100)->nullable()
                ->comment('Paint color if different from cabinet');

            $table->string('stain_color', 100)->nullable()
                ->comment('Stain color if different from cabinet');

            // ========================================
            // DECORATIVE HARDWARE
            // ========================================

            $table->boolean('has_decorative_hardware')->default(false)
                ->comment('Decorative handle/knob');

            $table->string('decorative_hardware_model', 100)->nullable()
                ->comment('Handle/knob model');

            // ========================================
            // PRODUCTION TRACKING
            // ========================================

            // Cutting Phase
            $table->timestamp('cnc_cut_at')->nullable()
                ->comment('When CNC cut drawer front');

            $table->timestamp('manually_cut_at')->nullable()
                ->comment('When manually cut/fabricated');

            $table->timestamp('edge_banded_at')->nullable()
                ->comment('When edge banding completed');

            // Assembly Phase
            $table->timestamp('box_assembled_at')->nullable()
                ->comment('When drawer box assembled');

            $table->timestamp('front_attached_at')->nullable()
                ->comment('When front attached to box');

            $table->timestamp('sanded_at')->nullable()
                ->comment('When sanding completed');

            // Finishing Phase
            $table->timestamp('finished_at')->nullable()
                ->comment('When finish applied and cured');

            // Installation Phase
            $table->timestamp('slides_installed_at')->nullable()
                ->comment('When slides installed');

            $table->timestamp('installed_in_cabinet_at')->nullable()
                ->comment('When installed into cabinet');

            // ========================================
            // QUALITY CONTROL
            // ========================================

            $table->boolean('qc_passed')->nullable()
                ->comment('Passed quality control inspection');

            $table->text('qc_notes')->nullable()
                ->comment('QC findings and issues');

            $table->timestamp('qc_inspected_at')->nullable()
                ->comment('When QC inspection performed');

            $table->foreignId('qc_inspector_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('User who performed QC');

            // ========================================
            // NOTES
            // ========================================

            $table->text('notes')->nullable()
                ->comment('Drawer-specific notes');

            // ========================================
            // METADATA
            // ========================================

            $table->timestamps();
            $table->softDeletes();

            // ========================================
            // INDEXES
            // ========================================

            $table->index('cabinet_specification_id', 'idx_drawers_cabinet');
            $table->index('section_id', 'idx_drawers_section');
            $table->index(['cnc_cut_at', 'finished_at'], 'idx_drawers_production');
            $table->index('qc_passed', 'idx_drawers_qc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_drawers');
    }
};
