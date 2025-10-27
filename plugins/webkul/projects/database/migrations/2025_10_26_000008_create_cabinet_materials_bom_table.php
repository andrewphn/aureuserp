<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cabinet Materials Bill of Materials (BOM)
     * Links cabinet specifications to required materials with quantities
     * Enables automatic material ordering and cost tracking
     */
    public function up(): void
    {
        Schema::create('cabinet_materials_bom', function (Blueprint $table) {
            $table->id();

            // Cabinet Reference (can be cabinet_specification OR cabinet_run for aggregation)
            $table->foreignId('cabinet_specification_id')->nullable()
                ->comment('Individual cabinet material requirement');
            $table->foreign('cabinet_specification_id')
                ->references('id')
                ->on('projects_cabinet_specifications')
                ->onDelete('cascade');

            $table->foreignId('cabinet_run_id')->nullable()
                ->comment('Aggregate materials for entire run');
            $table->foreign('cabinet_run_id')
                ->references('id')
                ->on('projects_cabinet_runs')
                ->onDelete('cascade');

            // Material Reference
            $table->foreignId('product_id')->constrained('products_products')
                ->comment('Material from products/inventory');
            $table->string('component_name', 100)->nullable()
                ->comment('What this material is for: box_sides, face_frame, doors, etc.');

            // Quantity Requirements
            $table->decimal('quantity_required', 10, 2)
                ->comment('Quantity needed in material UOM');
            $table->string('unit_of_measure', 50)->default('EA')
                ->comment('UOM: BF, SQFT, EA, LF, etc.');
            $table->decimal('waste_factor_percentage', 5, 2)->default(10.00)
                ->comment('Waste/scrap factor % (typically 10-15%)');
            $table->decimal('quantity_with_waste', 10, 2)
                ->comment('Calculated: quantity_required × (1 + waste_factor)');

            // Dimensioned Calculation (for sheet goods)
            $table->decimal('component_width_inches', 8, 3)->nullable()
                ->comment('Width of this component');
            $table->decimal('component_height_inches', 8, 3)->nullable()
                ->comment('Height/length of this component');
            $table->integer('quantity_of_components')->default(1)
                ->comment('Number of identical pieces');
            $table->decimal('sqft_per_component', 8, 2)->nullable()
                ->comment('Calculated square footage per piece');
            $table->decimal('total_sqft_required', 8, 2)->nullable()
                ->comment('Total SQFT: sqft_per_component × quantity × waste_factor');

            // Linear Feet Calculation (for solid wood)
            $table->decimal('linear_feet_per_component', 8, 2)->nullable()
                ->comment('Linear feet per piece');
            $table->decimal('total_linear_feet', 8, 2)->nullable()
                ->comment('Total LF needed');
            $table->decimal('board_feet_required', 8, 2)->nullable()
                ->comment('Calculated BF from LF × thickness × width');

            // Cost Tracking
            $table->decimal('unit_cost', 10, 2)->nullable()
                ->comment('Cost per UOM from product');
            $table->decimal('total_material_cost', 10, 2)->nullable()
                ->comment('Calculated: quantity_with_waste × unit_cost');

            // Material Specifications
            $table->string('grain_direction', 50)->nullable()
                ->comment('horizontal, vertical, none (affects sheet layout)');
            $table->boolean('requires_edge_banding')->default(false)
                ->comment('Exposed edges need banding');
            $table->string('edge_banding_sides', 50)->nullable()
                ->comment('Which edges: all, front_only, front_back, etc.');
            $table->decimal('edge_banding_lf', 8, 2)->nullable()
                ->comment('Linear feet of edge banding needed');

            // CNC/Machining Notes
            $table->text('cnc_notes')->nullable()
                ->comment('CNC machining requirements for this component');
            $table->text('machining_operations')->nullable()
                ->comment('Required operations: dado, groove, mortise, etc.');

            // Material Status
            $table->boolean('material_allocated')->default(false)
                ->comment('Material reserved from inventory');
            $table->timestamp('material_allocated_at')->nullable()
                ->comment('When material was allocated');
            $table->boolean('material_issued')->default(false)
                ->comment('Material physically issued to production');
            $table->timestamp('material_issued_at')->nullable()
                ->comment('When material was issued');

            // Substitutions
            $table->foreignId('substituted_product_id')->nullable()
                ->comment('Alternative material if primary not available');
            $table->foreign('substituted_product_id')
                ->references('id')
                ->on('products_products')
                ->onDelete('set null');
            $table->text('substitution_notes')->nullable()
                ->comment('Why substitution was made');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for material queries
            $table->index(['cabinet_specification_id', 'component_name'], 'idx_bom_cabinet_component');
            $table->index(['cabinet_run_id', 'product_id'], 'idx_bom_run_material');
            $table->index(['material_allocated', 'material_issued'], 'idx_bom_material_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabinet_materials_bom');
    }
};
