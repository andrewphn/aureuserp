<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds numeric_value column for NUMBER/DIMENSION attribute types
     * and makes attribute_option_id nullable for numeric types
     */
    public function up(): void
    {
        // First, drop the foreign key constraint on attribute_option_id
        Schema::table('products_product_attribute_values', function (Blueprint $table) {
            $table->dropForeign(['attribute_option_id']);
        });

        // Modify the column to be nullable and re-add the constraint
        Schema::table('products_product_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('attribute_option_id')->nullable()->change();

            // Re-add foreign key with cascade delete
            $table->foreign('attribute_option_id')
                ->references('id')
                ->on('products_attribute_options')
                ->nullOnDelete();

            // Add numeric value storage for NUMBER/DIMENSION types
            $table->decimal('numeric_value', 15, 4)->nullable()->after('extra_price')
                ->comment('Stores actual numeric value for NUMBER/DIMENSION attributes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_product_attribute_values', function (Blueprint $table) {
            $table->dropColumn('numeric_value');

            // Remove the nullable foreign key
            $table->dropForeign(['attribute_option_id']);
        });

        Schema::table('products_product_attribute_values', function (Blueprint $table) {
            // Restore original constraint (non-nullable)
            $table->unsignedBigInteger('attribute_option_id')->nullable(false)->change();

            $table->foreign('attribute_option_id')
                ->references('id')
                ->on('products_attribute_options')
                ->cascadeOnDelete();
        });
    }
};
