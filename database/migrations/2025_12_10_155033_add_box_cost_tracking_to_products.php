<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields for tracking box/package costs and auto-calculating unit costs:
     * - box_cost: What you pay when purchasing a box/package
     * - units_per_box: How many individual units come in a box
     * - cost_per_unit is auto-calculated: box_cost / units_per_box
     */
    public function up(): void
    {
        if (!Schema::hasTable('products_products')) {
            return;
        }

        Schema::table('products_products', function (Blueprint $table) {
            // Cost when buying a box/package from supplier
            $table->decimal('box_cost', 12, 4)->nullable()->after('cost');

            // How many units come in a box/package
            $table->integer('units_per_box')->nullable()->after('box_cost');

            // Package description (e.g., "Box of 100", "Case of 12", "Bundle of 50")
            $table->string('package_description')->nullable()->after('units_per_box');

            // Supplier part number for ordering
            $table->string('supplier_sku')->nullable()->after('package_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products_products')) {
            return;
        }

        Schema::table('products_products', function (Blueprint $table) {
            $table->dropColumn([
                'box_cost',
                'units_per_box',
                'package_description',
                'supplier_sku',
            ]);
        });
    }
};
