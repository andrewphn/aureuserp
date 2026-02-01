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
        // Skip if products plugin tables don't exist yet
        if (!Schema::hasTable('products_categories') || !Schema::hasTable('products_products')) {
            return;
        }

        Schema::create('products_reference_type_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->comment('Type code (e.g., GLUE, SAW, HINGE)');
            $table->string('name', 100)->comment('Display name (e.g., Glue, Saw Blade, Hinge)');
            $table->foreignId('category_id')->constrained('products_categories')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'code']);
            $table->index(['category_id', 'is_active']);
        });

        // Add type_code column to products table for storing the selected type
        Schema::table('products_products', function (Blueprint $table) {
            $table->string('type_code', 10)->nullable()->after('reference')->comment('Type code for reference generation');
            $table->foreignId('reference_type_code_id')->nullable()->after('type_code')->constrained('products_reference_type_codes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products_products')) {
            Schema::table('products_products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('reference_type_code_id');
                $table->dropColumn('type_code');
            });
        }

        Schema::dropIfExists('products_reference_type_codes');
    }
};
