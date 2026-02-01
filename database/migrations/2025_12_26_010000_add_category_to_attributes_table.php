<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds category grouping and constant flag to attributes
     * Categories: clearance, dimension, material, finish, hardware, cabinet
     */
    public function up(): void
    {
        if (!Schema::hasTable('products_attributes')) {
            return;
        }

        Schema::table('products_attributes', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('type')
                ->comment('Attribute category: clearance, dimension, material, finish, hardware, cabinet');
            $table->boolean('is_constant')->default(false)->after('decimal_places')
                ->comment('If true, value is a fixed constant for this product type');
            $table->decimal('default_value', 15, 4)->nullable()->after('is_constant')
                ->comment('Default/constant value for this attribute');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products_attributes')) {
            return;
        }

        Schema::table('products_attributes', function (Blueprint $table) {
            $table->dropColumn(['category', 'is_constant', 'default_value']);
        });
    }
};
