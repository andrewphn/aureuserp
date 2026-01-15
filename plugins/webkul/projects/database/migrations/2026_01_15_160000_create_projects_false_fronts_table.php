<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the projects_false_fronts table for storing false front components.
     *
     * False fronts (dummy drawers/decorative panels) are panels that look like drawers
     * but don't open. Common uses:
     * - Sink base cabinets (panel above sink where plumbing prevents real drawer)
     * - Appliance openings (decorative panels to match surrounding fronts)
     * - Tilt-out trays (some false fronts hinge at bottom for sponge/brush storage)
     *
     * Key construction element: BACKING RAIL - support strip behind false front
     * that provides mounting surface and structural support.
     */
    public function up(): void
    {
        Schema::create('projects_false_fronts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')
                ->constrained('projects_cabinets')
                ->onDelete('cascade');
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('projects_cabinet_sections')
                ->onDelete('cascade');

            // Identification
            $table->integer('false_front_number')->default(1);
            $table->string('false_front_name', 100)->nullable();
            $table->string('full_code', 255)->nullable();
            $table->integer('sort_order')->default(0);

            // Type: fixed (standard) or tilt_out (hinges at bottom)
            $table->enum('false_front_type', ['fixed', 'tilt_out'])->default('fixed');

            // =========================================
            // FALSE FRONT PANEL DIMENSIONS
            // =========================================
            $table->decimal('width_inches', 8, 4)->nullable();
            $table->decimal('height_inches', 8, 4)->nullable();
            $table->decimal('thickness_inches', 5, 3)->default(0.75);

            // =========================================
            // BACKING RAIL (Critical for mounting!)
            // The support strip behind the false front
            // =========================================
            $table->boolean('has_backing_rail')->default(true);
            $table->decimal('backing_rail_width_inches', 5, 3)->default(3.5);     // Width of rail (3-1/2" typical)
            $table->decimal('backing_rail_height_inches', 5, 3)->nullable();       // Usually = false front height
            $table->decimal('backing_rail_thickness_inches', 5, 3)->default(0.75); // 3/4" standard
            $table->string('backing_rail_material', 100)->default('plywood');
            $table->string('backing_rail_position', 50)->default('center');        // top, center, bottom

            // =========================================
            // STYLE (matches doors/drawers for consistency)
            // =========================================
            $table->string('profile_type', 100)->nullable();       // e.g., 'shaker', 'slab'
            $table->decimal('rail_width_inches', 5, 3)->nullable(); // For 5-piece construction
            $table->decimal('stile_width_inches', 5, 3)->nullable();

            // Finish
            $table->string('finish_type', 100)->nullable();
            $table->string('paint_color', 100)->nullable();
            $table->string('stain_color', 100)->nullable();

            // =========================================
            // HARDWARE
            // =========================================
            // Tilt-out hardware (for tilt_out type)
            $table->boolean('has_tilt_hardware')->default(false);
            $table->string('tilt_hardware_model', 100)->nullable();
            $table->foreignId('tilt_hardware_product_id')
                ->nullable()
                ->constrained('products_products')
                ->onDelete('set null');

            // Decorative hardware (optional pull for aesthetics)
            $table->boolean('has_decorative_hardware')->default(false);
            $table->string('decorative_hardware_model', 100)->nullable();
            $table->foreignId('decorative_hardware_product_id')
                ->nullable()
                ->constrained('products_products')
                ->onDelete('set null');

            // Mounting hardware (screws to backing rail)
            $table->decimal('mounting_screw_length_inches', 4, 2)->default(1.0);   // 1" typical for inset
            $table->integer('mounting_screw_count')->default(4);

            // =========================================
            // OPENING POSITION TRACKING (HasOpeningPosition trait)
            // =========================================
            $table->decimal('position_in_opening_inches', 8, 4)->nullable();
            $table->decimal('consumed_height_inches', 8, 4)->nullable();
            $table->decimal('position_from_left_inches', 8, 4)->nullable();
            $table->decimal('consumed_width_inches', 8, 4)->nullable();

            // =========================================
            // PRODUCTION TRACKING
            // =========================================
            // False Front Panel
            $table->timestamp('panel_cnc_cut_at')->nullable();
            $table->timestamp('panel_edge_banded_at')->nullable();
            $table->timestamp('panel_sanded_at')->nullable();
            $table->timestamp('panel_finished_at')->nullable();

            // Backing Rail
            $table->timestamp('backing_rail_cut_at')->nullable();
            $table->timestamp('backing_rail_installed_at')->nullable();

            // Final Installation
            $table->timestamp('installed_at')->nullable();

            // QC
            $table->boolean('qc_passed')->nullable();
            $table->text('qc_notes')->nullable();

            // Product link
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products_products')
                ->onDelete('set null');

            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('cabinet_id');
            $table->index('section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_false_fronts');
    }
};
