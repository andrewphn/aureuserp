<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sales order line items - detailed breakdown of what's being sold
     * Example from Watchtower: "Shelving Unit 1: 8 units @ $1,924.26 = $15,394.08"
     * Links to project entities (room, location, run, or cabinet)
     */
    public function up(): void
    {
        Schema::create('sales_order_line_items', function (Blueprint $table) {
            $table->id();

            // Parent Order
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');

            // Linkage to Project Entities (one of these will be filled)
            $table->foreignId('project_id')->nullable()
                ->comment('Project this line item belongs to');
            $table->foreign('project_id')
                ->references('id')
                ->on('projects_projects')
                ->onDelete('cascade');

            $table->foreignId('room_id')->nullable()
                ->comment('Room if line item is room-level');
            $table->foreign('room_id')
                ->references('id')
                ->on('projects_rooms')
                ->onDelete('set null');

            $table->foreignId('room_location_id')->nullable()
                ->comment('Location if line item is location-level (e.g., "Sink Wall")');
            $table->foreign('room_location_id')
                ->references('id')
                ->on('projects_room_locations')
                ->onDelete('set null');

            $table->foreignId('cabinet_run_id')->nullable()
                ->comment('Cabinet run if line item is run-level');
            $table->foreign('cabinet_run_id')
                ->references('id')
                ->on('projects_cabinet_runs')
                ->onDelete('set null');

            $table->foreignId('cabinet_specification_id')->nullable()
                ->comment('Individual cabinet if line item is cabinet-level');
            $table->foreign('cabinet_specification_id')
                ->references('id')
                ->on('projects_cabinet_specifications')
                ->onDelete('set null');

            // Product/Inventory Linkage (for non-cabinet items or finished goods)
            $table->foreignId('product_id')->nullable()
                ->comment('Link to inventory product if applicable');
            $table->foreign('product_id')
                ->references('id')
                ->on('products_products')
                ->onDelete('set null');

            // Line Item Description
            $table->integer('sequence')->default(0)
                ->comment('Display order on invoice');
            $table->string('line_item_type', 50)->nullable()
                ->comment('room, location, cabinet_run, cabinet, product, service, custom');
            $table->string('description', 500)
                ->comment('Line item description (e.g., "Project Deposit - Kitchen Cabinets")');
            $table->text('detailed_description')->nullable()
                ->comment('Longer description with specifications');

            // Quantity & Unit
            $table->decimal('quantity', 10, 2)->default(1)
                ->comment('Quantity of units');
            $table->string('unit_of_measure', 50)->default('EA')
                ->comment('EA, LF, BF, SQFT, HR, etc.');

            // Pricing
            $table->decimal('unit_price', 10, 2)->default(0)
                ->comment('Price per unit');
            $table->decimal('subtotal', 10, 2)->default(0)
                ->comment('Calculated: quantity × unit_price');
            $table->decimal('discount_percentage', 5, 2)->nullable()
                ->comment('Discount % if applicable');
            $table->decimal('discount_amount', 10, 2)->nullable()
                ->comment('Calculated discount amount');
            $table->decimal('line_total', 10, 2)->default(0)
                ->comment('Final line total: subtotal - discount');

            // Tax
            $table->boolean('taxable')->default(true)
                ->comment('Is this line item taxable');
            $table->decimal('tax_rate', 5, 2)->nullable()
                ->comment('Tax rate % for this item');
            $table->decimal('tax_amount', 10, 2)->nullable()
                ->comment('Calculated tax amount');

            // Linear Feet Pricing Details (for woodworking)
            $table->decimal('linear_feet', 8, 2)->nullable()
                ->comment('Linear feet for this line item');
            $table->integer('complexity_tier')->nullable()
                ->comment('1-5 pricing tier');
            $table->decimal('base_rate_per_lf', 8, 2)->nullable()
                ->comment('Base $/LF rate');
            $table->decimal('material_rate_per_lf', 8, 2)->nullable()
                ->comment('Material upgrade $/LF');
            $table->decimal('combined_rate_per_lf', 8, 2)->nullable()
                ->comment('Combined rate: base + material');

            // Material Details (visible on line item)
            $table->string('material_type', 100)->nullable()
                ->comment('Material description for client');
            $table->string('wood_species', 100)->nullable()
                ->comment('Wood species for client');
            $table->string('finish_type', 100)->nullable()
                ->comment('Finish description');

            // Special Features (for line item display)
            $table->text('features_list')->nullable()
                ->comment('Bulleted list of features for this item');
            $table->text('hardware_list')->nullable()
                ->comment('Hardware included with this item');

            // Notes
            $table->text('client_notes')->nullable()
                ->comment('Notes visible to client on invoice');
            $table->text('internal_notes')->nullable()
                ->comment('Internal notes not visible to client');

            // Production Linkage
            $table->boolean('requires_production')->default(false)
                ->comment('Does this item need to be built');
            $table->string('production_status', 50)->nullable()
                ->comment('pending, in_progress, completed');
            $table->timestamp('production_completed_at')->nullable()
                ->comment('When production of this item completed');

            // Timestamps
            $table->foreignId('creator_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['sales_order_id', 'sequence'], 'idx_line_item_order');
            $table->index(['project_id', 'room_id'], 'idx_line_item_project');
            $table->index(['line_item_type', 'requires_production'], 'idx_line_item_production');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_line_items');
    }
};
