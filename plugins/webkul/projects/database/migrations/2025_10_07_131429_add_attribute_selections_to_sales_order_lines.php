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
     * Add attribute_selections column to store product attribute configurations
     * for sales order line items.
     *
     * Example JSON structure:
     * [
     *   {
     *     "attribute_id": 11,
     *     "attribute_name": "Pricing Level",
     *     "option_id": 58,
     *     "option_name": "Level 2 - Standard ($168/LF)",
     *     "extra_price": 0.00
     *   },
     *   {
     *     "attribute_id": 12,
     *     "attribute_name": "Material Category",
     *     "option_id": 63,
     *     "option_name": "Paint Grade (Hard Maple/Poplar)",
     *     "extra_price": 138.00
     *   }
     * ]
     */
    public function up(): void
    {
        // Skip if column already exists
        if (Schema::hasColumn('sales_order_lines', 'attribute_selections')) {
            return;
        }

        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->json('attribute_selections')->nullable()->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->dropColumn('attribute_selections');
        });
    }
};
