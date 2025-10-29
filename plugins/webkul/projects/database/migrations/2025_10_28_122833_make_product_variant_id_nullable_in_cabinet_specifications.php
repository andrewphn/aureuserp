<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Make product_variant_id nullable to allow cabinet creation from PDF annotations
     * without requiring a product selection upfront. Product can be assigned later.
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['product_variant_id']);

            // Make the column nullable
            $table->foreignId('product_variant_id')
                ->nullable()
                ->change();

            // Re-add foreign key constraint with nullable
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('products_products')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['product_variant_id']);

            // Make the column non-nullable again
            $table->foreignId('product_variant_id')
                ->nullable(false)
                ->change();

            // Re-add foreign key constraint
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('products_products')
                ->onDelete('restrict');
        });
    }
};
