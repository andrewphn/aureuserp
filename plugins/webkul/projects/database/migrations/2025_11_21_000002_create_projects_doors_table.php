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
     * Creates doors table for all door components.
     *
     * Meeting Reference: "It has to be at the component level. Because in one cabinet
     * you could have a blind door and a regular door." (01:20:30)
     *
     * Each door is tracked individually with its own specifications, hardware, and production timeline.
     */
    public function up(): void
    {
        Schema::create('projects_doors', function (Blueprint $table) {
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

            $table->integer('door_number')->default(1)
                ->comment('Door position (1, 2, 3...)');

            $table->string('door_name', 100)->nullable()
                ->comment('Door name: D1, D2, etc.');

            $table->integer('sort_order')->default(0)
                ->comment('Display order within cabinet/section');

            // ========================================
            // DIMENSIONS
            // ========================================

            $table->decimal('width_inches', 8, 3)
                ->comment('Door width');

            $table->decimal('height_inches', 8, 3)
                ->comment('Door height');

            // ========================================
            // DOOR CONSTRUCTION
            // ========================================

            $table->decimal('rail_width_inches', 5, 3)->nullable()
                ->comment('Horizontal rail width');

            $table->decimal('style_width_inches', 5, 3)->nullable()
                ->comment('Vertical style width');

            $table->boolean('has_check_rail')->default(false)
                ->comment('Has center horizontal rail');

            $table->decimal('check_rail_width_inches', 5, 3)->nullable()
                ->comment('Check rail width if applicable');

            $table->string('profile_type', 100)->nullable()
                ->comment('shaker, flat_panel, beaded, raised_panel');

            $table->string('fabrication_method', 50)->nullable()
                ->comment('cnc, five_piece_manual, slab');

            $table->decimal('thickness_inches', 5, 3)->nullable()
                ->comment('Door thickness');

            // ========================================
            // DOOR HARDWARE
            // ========================================

            $table->string('hinge_type', 100)->nullable()
                ->comment('blind_inset, half_overlay, full_overlay, euro_concealed');

            $table->string('hinge_model', 100)->nullable()
                ->comment('Blum 71B9790, etc.');

            $table->integer('hinge_quantity')->nullable()
                ->comment('Number of hinges (typically 2-3)');

            $table->string('hinge_side', 20)->nullable()
                ->comment('left, right');

            // ========================================
            // GLASS DOORS
            // ========================================

            $table->boolean('has_glass')->default(false)
                ->comment('Glass panel door');

            $table->string('glass_type', 100)->nullable()
                ->comment('clear, seeded, frosted, mullioned');

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
                ->comment('When CNC cut this door');

            $table->timestamp('manually_cut_at')->nullable()
                ->comment('When manually cut/fabricated');

            $table->timestamp('edge_banded_at')->nullable()
                ->comment('When edge banding completed');

            // Assembly Phase (for 5-piece doors)
            $table->timestamp('assembled_at')->nullable()
                ->comment('When door assembled (5-piece construction)');

            $table->timestamp('sanded_at')->nullable()
                ->comment('When sanding completed');

            // Finishing Phase
            $table->timestamp('finished_at')->nullable()
                ->comment('When finish applied and cured');

            // Installation Phase
            $table->timestamp('hardware_installed_at')->nullable()
                ->comment('When hinges installed');

            $table->timestamp('installed_in_cabinet_at')->nullable()
                ->comment('When hung in cabinet');

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
                ->comment('Door-specific notes');

            // ========================================
            // METADATA
            // ========================================

            $table->timestamps();
            $table->softDeletes();

            // ========================================
            // INDEXES
            // ========================================

            $table->index('cabinet_specification_id', 'idx_doors_cabinet');
            $table->index('section_id', 'idx_doors_section');
            $table->index(['cnc_cut_at', 'finished_at'], 'idx_doors_production');
            $table->index('qc_passed', 'idx_doors_qc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_doors');
    }
};
