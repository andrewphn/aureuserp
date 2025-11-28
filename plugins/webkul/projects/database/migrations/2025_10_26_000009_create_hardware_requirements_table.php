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
     * Hardware requirements tracking per cabinet
     * Tracks Blum hinges, drawer slides, shelf pins, Rev-a-Shelf accessories
     * Supports both per-cabinet detail (Levi's assembly view) and run-level aggregation (ordering)
     */
    public function up(): void
    {
        Schema::create('hardware_requirements', function (Blueprint $table) {
            $table->id();

            // Cabinet Reference (individual or run-level aggregation)
            $table->foreignId('cabinet_specification_id')->nullable()
                ->comment('Individual cabinet hardware requirement');
            $table->foreign('cabinet_specification_id')
                ->references('id')
                ->on('projects_cabinet_specifications')
                ->onDelete('cascade');

            $table->foreignId('cabinet_run_id')->nullable()
                ->comment('Aggregate hardware for entire run (for ordering)');
            $table->foreign('cabinet_run_id')
                ->references('id')
                ->on('projects_cabinet_runs')
                ->onDelete('cascade');

            // Hardware Item Reference
            $table->foreignId('product_id')->constrained('products_products')
                ->comment('Hardware product from inventory');

            // Hardware Classification
            $table->string('hardware_type', 50)
                ->comment('hinge, slide, shelf_pin, pullout, lazy_susan, organizer, knob, pull');
            $table->string('manufacturer', 100)->nullable()
                ->comment('Manufacturer: Blum, Rev-a-Shelf, etc.');
            $table->string('model_number', 100)->nullable()
                ->comment('Specific model: 71B9790, 562H-11CR-1, etc.');

            // Quantity Requirements
            $table->integer('quantity_required')
                ->comment('Number of units needed');
            $table->string('unit_of_measure', 50)->default('EA')
                ->comment('Usually EA for hardware');

            // Application Details
            $table->string('applied_to', 100)->nullable()
                ->comment('What it goes on: door, drawer, shelf, corner, etc.');
            $table->integer('door_number')->nullable()
                ->comment('Which door if multiple (1, 2, 3, etc.)');
            $table->integer('drawer_number')->nullable()
                ->comment('Which drawer if multiple');
            $table->string('mounting_location', 100)->nullable()
                ->comment('left, right, top, bottom, center');

            // Specifications (for hinges)
            $table->string('hinge_type', 50)->nullable()
                ->comment('concealed, overlay, inset, soft_close');
            $table->integer('hinge_opening_angle')->nullable()
                ->comment('Opening angle: 110, 120, 170 degrees');
            $table->decimal('overlay_dimension_mm', 5, 2)->nullable()
                ->comment('Overlay in mm (0, 3, 6, etc.)');

            // Specifications (for drawer slides)
            $table->string('slide_type', 50)->nullable()
                ->comment('undermount, side_mount, soft_close, push_to_open');
            $table->decimal('slide_length_inches', 5, 1)->nullable()
                ->comment('Slide length: 12, 15, 18, 21, 24 inches');
            $table->integer('slide_weight_capacity_lbs')->nullable()
                ->comment('Weight capacity in pounds');

            // Specifications (for shelf pins)
            $table->string('shelf_pin_type', 50)->nullable()
                ->comment('standard, glass, metal, plastic');
            $table->decimal('shelf_pin_diameter_mm', 5, 2)->nullable()
                ->comment('Pin diameter (typically 5mm)');

            // Specifications (for accessories)
            $table->decimal('accessory_width_inches', 8, 3)->nullable()
                ->comment('Width of accessory (for Rev-a-Shelf sizing)');
            $table->decimal('accessory_depth_inches', 8, 3)->nullable()
                ->comment('Depth of accessory');
            $table->decimal('accessory_height_inches', 8, 3)->nullable()
                ->comment('Height of accessory');
            $table->string('accessory_configuration', 100)->nullable()
                ->comment('Configuration: single, double, triple, chrome, maple, etc.');

            // Finish/Color (for visible hardware)
            $table->string('finish', 100)->nullable()
                ->comment('Finish: nickel, chrome, oil_rubbed_bronze, etc.');
            $table->string('color_match', 100)->nullable()
                ->comment('Color to match: cabinet_finish, stainless, etc.');

            // Cost Tracking
            $table->decimal('unit_cost', 10, 2)->nullable()
                ->comment('Cost per unit from product');
            $table->decimal('total_hardware_cost', 10, 2)->nullable()
                ->comment('Calculated: quantity × unit_cost');

            // Installation Instructions
            $table->text('installation_notes')->nullable()
                ->comment('Special installation considerations');
            $table->integer('install_sequence')->nullable()
                ->comment('Order of installation (1, 2, 3...)');
            $table->boolean('requires_jig')->default(false)
                ->comment('Requires installation jig/template');
            $table->string('jig_name', 100)->nullable()
                ->comment('Which jig to use');

            // Kitting & Assembly
            $table->boolean('hardware_kitted')->default(false)
                ->comment('Included in hardware kit');
            $table->timestamp('hardware_kitted_at')->nullable()
                ->comment('When added to kit');
            $table->boolean('hardware_installed')->default(false)
                ->comment('Hardware installed on cabinet');
            $table->timestamp('hardware_installed_at')->nullable()
                ->comment('When installed');
            $table->foreignId('installed_by_user_id')->nullable()->constrained('users')
                ->comment('Craftsman who installed');

            // Material Status (inventory tracking)
            $table->boolean('hardware_allocated')->default(false)
                ->comment('Reserved from inventory');
            $table->timestamp('hardware_allocated_at')->nullable()
                ->comment('When allocated');
            $table->boolean('hardware_issued')->default(false)
                ->comment('Physically issued to production');
            $table->timestamp('hardware_issued_at')->nullable()
                ->comment('When issued');

            // Substitutions
            $table->foreignId('substituted_product_id')->nullable()
                ->comment('Alternative hardware if primary not available');
            $table->foreign('substituted_product_id')
                ->references('id')
                ->on('products_products')
                ->onDelete('set null');
            $table->text('substitution_reason')->nullable()
                ->comment('Why substitution was made');

            // Defects/Returns
            $table->boolean('has_defect')->default(false)
                ->comment('Hardware item defective');
            $table->text('defect_description')->nullable()
                ->comment('Description of defect');
            $table->boolean('returned_to_supplier')->default(false)
                ->comment('Returned for replacement');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for hardware queries
            $table->index(['cabinet_specification_id', 'hardware_type'], 'idx_hardware_cabinet_type');
            $table->index(['cabinet_run_id', 'product_id'], 'idx_hardware_run_product');
            $table->index(['hardware_kitted', 'hardware_installed'], 'idx_hardware_status');
            $table->index(['manufacturer', 'model_number'], 'idx_hardware_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hardware_requirements');
    }
};
