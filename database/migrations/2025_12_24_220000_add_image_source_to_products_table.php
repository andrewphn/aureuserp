<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('products_products')) {
            return;
        }

        Schema::table('products_products', function (Blueprint $table) {
            $table->string('image_source', 50)->nullable()->after('source_url')
                ->comment('Source of product image: user_photo, commercial, ai_generated');
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
            $table->dropColumn('image_source');
        });
    }
};
