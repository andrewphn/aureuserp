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
     * Creates cabinet specifications table to store custom dimensions and details
     * for each cabinet ordered. This bridges the gap between:
     * - Product Variants (pricing/catalog)
     * - Actual Fabrication (shop floor needs)
     *
     * Workflow:
     * 1. Bryan selects product variant → gets price/LF
     * 2. Enters cabinet dimensions → stores here
     * 3. System calculates total price
     * 4. Shop floor uses dimensions for cut lists/production
     */
    public function up(): void
    {
        if (Schema::hasTable('projects_cabinet_specifications')) {
            return;
        }

        Schema::create('projects_cabinet_specifications', function (Blueprint $table) {
            $table->id();

            // Links to order/quote (no FK constraint - sales_order_lines may not exist)
            $table->unsignedBigInteger('order_line_id')
                ->nullable()
                ->comment('Links to order line item (for quotes/orders)');

            // Project reference (no FK constraint at create time for flexibility)
            $table->unsignedBigInteger('project_id')
                ->nullable()
                ->comment('Optional direct link to project');

            // Product variant selected (for pricing/attributes)
            $table->unsignedBigInteger('product_variant_id')
                ->nullable()
                ->comment('Selected cabinet variant (determines price/LF and attributes)');

            // Physical Dimensions (what shop needs to build)
            $table->decimal('length_inches', 8, 2)
                ->comment('Cabinet length in inches (determines linear feet)');
            $table->decimal('width_inches', 8, 2)
                ->nullable()
                ->comment('Cabinet width/depth in inches');
            $table->decimal('depth_inches', 8, 2)
                ->nullable()
                ->comment('Cabinet depth in inches (12" for wall, 24" for base standard)');
            $table->decimal('height_inches', 8, 2)
                ->nullable()
                ->comment('Cabinet height in inches (30" for base, 84-96" for tall)');

            // Calculated measurements
            $table->decimal('linear_feet', 8, 2)
                ->comment('Calculated: length_inches / 12, used for pricing');

            // Quantities
            $table->unsignedInteger('quantity')
                ->default(1)
                ->comment('Number of identical cabinets with these specs');

            // Pricing (auto-calculated from variant price × linear_feet × quantity)
            $table->decimal('unit_price_per_lf', 10, 2)
                ->comment('Price per linear foot (from product variant)');
            $table->decimal('total_price', 10, 2)
                ->comment('Calculated: unit_price_per_lf × linear_feet × quantity');

            // Custom specifications (for fabrication)
            $table->text('hardware_notes')->nullable()
                ->comment('Hardware selections: hinges, pulls, slides, etc.');
            $table->text('custom_modifications')->nullable()
                ->comment('Custom requests: extra shelves, lazy susan, etc.');
            $table->text('shop_notes')->nullable()
                ->comment('Internal notes for production team');

            // Metadata
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('order_line_id');
            $table->index('project_id');
            $table->index('product_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_cabinet_specifications');
    }
};
