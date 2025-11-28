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
     * Make product_variant_id nullable to allow cabinet creation from PDF annotations
     * without requiring a product selection upfront. Product can be assigned later.
     */
    public function up(): void
    {
        // Check if table exists
        if (!Schema::hasTable('projects_cabinet_specifications')) {
            return;
        }

        // Try to drop foreign key if it exists (ignore if it doesn't)
        try {
            Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
                $table->dropForeign(['product_variant_id']);
            });
        } catch (\Exception $e) {
            // Foreign key doesn't exist, that's fine
        }

        // Make the column nullable (it should already be nullable from newer migrations)
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')
                ->nullable()
                ->change();
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
