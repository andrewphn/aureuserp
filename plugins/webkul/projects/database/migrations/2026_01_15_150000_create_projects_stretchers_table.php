<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates stretchers table for horizontal support rails in base cabinets.
     *
     * Stretchers are horizontal wooden rails at the top of base cabinets that:
     * - Hold the cabinet square and stable
     * - Provide a surface to attach the countertop
     * - Give drawer slides something to mount to
     *
     * Rule: Number of stretchers = 2 (front + back) + drawer_count (one per drawer)
     *
     * Reference: Master Plan (iridescent-wishing-turing.md) lines 1456-1847
     */
    public function up(): void
    {
        Schema::create('projects_stretchers', function (Blueprint $table) {
            $table->id();

            // ========================================
            // RELATIONSHIPS
            // ========================================

            $table->foreignId('cabinet_id')
                ->constrained('projects_cabinets')
                ->onDelete('cascade')
                ->comment('Parent cabinet');

            $table->foreignId('section_id')
                ->nullable()
                ->constrained('projects_cabinet_sections')
                ->onDelete('set null')
                ->comment('Parent section (optional)');

            // ========================================
            // POSITION IDENTIFICATION
            // ========================================

            $table->enum('position', ['front', 'back', 'drawer_support'])
                ->comment('Stretcher position: front, back, or drawer_support');

            $table->integer('stretcher_number')->default(1)
                ->comment('Stretcher position number (1, 2, 3...)');

            $table->string('full_code', 100)->nullable()
                ->comment('Hierarchical code (e.g., TCS-0554-K1-SW-B1-STR1)');

            // ========================================
            // DIMENSIONS
            // ========================================

            $table->decimal('width_inches', 8, 4)
                ->comment('Stretcher width (usually = cabinet inside width)');

            $table->decimal('depth_inches', 8, 4)
                ->comment('Stretcher depth (typically 3-4 inches)');

            $table->decimal('thickness_inches', 5, 3)->default(0.75)
                ->comment('Stretcher thickness (standard 3/4 inch)');

            // ========================================
            // POSITION FROM CABINET EDGES
            // ========================================

            $table->decimal('position_from_front_inches', 8, 4)->nullable()
                ->comment('Distance from cabinet front edge (0 for front stretcher)');

            $table->decimal('position_from_top_inches', 8, 4)->nullable()
                ->comment('Distance from cabinet top (usually 0, flush with top)');

            // ========================================
            // MATERIAL
            // ========================================

            $table->string('material', 100)->default('plywood')
                ->comment('Stretcher material: plywood, solid_wood, mdf');

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products_products')
                ->onDelete('set null')
                ->comment('Linked inventory product for material tracking');

            // ========================================
            // DRAWER RELATIONSHIP
            // ========================================

            $table->boolean('supports_drawer')->default(true)
                ->comment('Whether this stretcher supports a drawer');

            $table->foreignId('drawer_id')
                ->nullable()
                ->constrained('projects_drawers')
                ->onDelete('set null')
                ->comment('Linked drawer for drawer_support position');

            // ========================================
            // CNC CUT LIST (Shop Rounding)
            // ========================================

            $table->decimal('cut_width_inches', 8, 4)->nullable()
                ->comment('Exact cut width for CNC');

            $table->decimal('cut_width_shop_inches', 8, 4)->nullable()
                ->comment('Shop-rounded width (to 1/16 inch)');

            $table->decimal('cut_depth_inches', 8, 4)->nullable()
                ->comment('Exact cut depth for CNC');

            $table->decimal('cut_depth_shop_inches', 8, 4)->nullable()
                ->comment('Shop-rounded depth (to 1/16 inch)');

            // ========================================
            // PRODUCTION TRACKING
            // ========================================

            $table->timestamp('cut_at')->nullable()
                ->comment('When stretcher was cut');

            $table->timestamp('edge_banded_at')->nullable()
                ->comment('When edge banding completed');

            $table->timestamp('installed_at')->nullable()
                ->comment('When installed in cabinet');

            // ========================================
            // QUALITY CONTROL
            // ========================================

            $table->boolean('qc_passed')->nullable()
                ->comment('Passed quality control inspection');

            $table->text('qc_notes')->nullable()
                ->comment('QC findings and issues');

            // ========================================
            // NOTES
            // ========================================

            $table->text('notes')->nullable()
                ->comment('Stretcher-specific notes');

            // ========================================
            // METADATA
            // ========================================

            $table->timestamps();
            $table->softDeletes();

            // ========================================
            // INDEXES
            // ========================================

            $table->index('cabinet_id', 'idx_stretchers_cabinet');
            $table->index('section_id', 'idx_stretchers_section');
            $table->index('drawer_id', 'idx_stretchers_drawer');
            $table->index('position', 'idx_stretchers_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_stretchers');
    }
};
