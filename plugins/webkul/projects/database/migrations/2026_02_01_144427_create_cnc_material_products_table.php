<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates mapping between CNC material codes and inventory products.
     * This enables:
     * - Material usage tracking from CNC programs
     * - Stock level visibility for CNC materials
     * - Purchase order generation for needed materials
     */
    public function up(): void
    {
        // CNC Material to Product mapping table
        Schema::create('projects_cnc_material_products', function (Blueprint $table) {
            $table->id();

            // The CNC material code (matches CncProgram.material_code)
            $table->string('material_code', 50)->index()
                ->comment('CNC material code: FL, PreFin, RiftWOPly, etc.');

            // Link to inventory product
            $table->foreignId('product_id')
                ->constrained('products_products')
                ->cascadeOnDelete()
                ->comment('The inventory product for this material');

            // Material properties
            $table->string('material_type')->default('sheet_goods')
                ->comment('sheet_goods, solid_wood, mdf, melamine');

            // Sheet/unit dimensions
            $table->string('sheet_size')->nullable()
                ->comment('Standard sheet size: 48x96, 48x120, etc.');
            $table->decimal('thickness_inches', 5, 3)->nullable()
                ->comment('Material thickness in inches');
            $table->decimal('sqft_per_sheet', 8, 2)->default(32)
                ->comment('Square feet per sheet (32 for 4x8)');

            // Cost tracking
            $table->decimal('cost_per_sheet', 10, 2)->nullable()
                ->comment('Current cost per sheet');
            $table->decimal('cost_per_sqft', 10, 4)->nullable()
                ->comment('Cost per square foot');

            // Vendor info
            $table->foreignId('preferred_vendor_id')->nullable()
                ->constrained('partners_partners')
                ->nullOnDelete()
                ->comment('Preferred vendor/supplier for this material');
            $table->string('vendor_sku')->nullable()
                ->comment('Vendor part number');
            $table->integer('lead_time_days')->nullable()
                ->comment('Typical lead time from vendor');

            // Stock management
            $table->integer('min_stock_sheets')->default(0)
                ->comment('Minimum sheets to keep in stock');
            $table->integer('reorder_qty_sheets')->default(0)
                ->comment('Quantity to order when reordering');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)
                ->comment('Default product for this material code');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Ensure only one default per material code
            $table->unique(['material_code', 'is_default'], 'cnc_material_default_unique');
        });

        // Add product_id to CNC programs for direct product link
        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            $table->foreignId('material_product_id')->nullable()
                ->after('material_code')
                ->constrained('products_products')
                ->nullOnDelete()
                ->comment('Direct link to inventory product');
        });

        // Track material usage per CNC program (for ordering)
        Schema::create('projects_cnc_material_usage', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cnc_program_id')
                ->constrained('projects_cnc_programs')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products_products')
                ->cascadeOnDelete();

            // Usage quantities
            $table->decimal('sheets_required', 8, 2)->default(0)
                ->comment('Number of sheets needed');
            $table->decimal('sqft_required', 10, 2)->default(0)
                ->comment('Square footage needed');
            $table->decimal('sheets_used', 8, 2)->nullable()
                ->comment('Actual sheets used (after nesting)');
            $table->decimal('sqft_used', 10, 2)->nullable()
                ->comment('Actual sqft used');
            $table->decimal('waste_sqft', 10, 2)->nullable()
                ->comment('Waste generated');

            // Cost tracking
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();

            // Stock allocation
            $table->enum('allocation_status', ['pending', 'reserved', 'issued', 'returned'])
                ->default('pending');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('issued_at')->nullable();

            // Link to purchase order if material was ordered
            $table->foreignId('purchase_order_id')->nullable()
                ->comment('PO created to order this material');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cnc_program_id', 'product_id']);
            $table->index('allocation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_cnc_material_usage');

        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            $table->dropForeign(['material_product_id']);
            $table->dropColumn('material_product_id');
        });

        Schema::dropIfExists('projects_cnc_material_products');
    }
};
