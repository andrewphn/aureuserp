<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add product FK fields for hardware associations across cabinet component tables.
 * This links hardware specs (hinges, slides, etc.) to actual inventory products.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Doors: Link hinge and decorative hardware to products
        Schema::table('projects_doors', function (Blueprint $table) {
            $table->foreignId('hinge_product_id')->nullable()->after('hinge_model')
                ->constrained('products_products')->nullOnDelete();
            $table->foreignId('decorative_hardware_product_id')->nullable()->after('decorative_hardware_model')
                ->constrained('products_products')->nullOnDelete();
        });

        // Drawers: Link slides and decorative hardware to products
        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->foreignId('slide_product_id')->nullable()->after('slide_model')
                ->constrained('products_products')->nullOnDelete();
            $table->foreignId('decorative_hardware_product_id')->nullable()->after('decorative_hardware_model')
                ->constrained('products_products')->nullOnDelete();
        });

        // Shelves: Link slides (for roll-out shelves) to products
        Schema::table('projects_shelves', function (Blueprint $table) {
            $table->foreignId('slide_product_id')->nullable()->after('slide_model')
                ->constrained('products_products')->nullOnDelete();
        });

        // Pullouts: Link slide hardware to products
        Schema::table('projects_pullouts', function (Blueprint $table) {
            $table->foreignId('slide_product_id')->nullable()->after('slide_model')
                ->constrained('products_products')->nullOnDelete();
        });

        // Cabinets: Link hinge, slide, and specialty hardware to products
        Schema::table('projects_cabinets', function (Blueprint $table) {
            $table->foreignId('hinge_product_id')->nullable()->after('hinge_model')
                ->constrained('products_products')->nullOnDelete();
            $table->foreignId('slide_product_id')->nullable()->after('slide_model')
                ->constrained('products_products')->nullOnDelete();
            $table->foreignId('pullout_product_id')->nullable()->after('pullout_model')
                ->constrained('products_products')->nullOnDelete();
            $table->foreignId('lazy_susan_product_id')->nullable()->after('lazy_susan_model')
                ->constrained('products_products')->nullOnDelete();
        });

        // Cabinet Sections: Add product association for the section component type
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('section_type')
                ->constrained('products_products')->nullOnDelete();
            $table->foreignId('hardware_product_id')->nullable()->after('product_id')
                ->constrained('products_products')->nullOnDelete();
        });

        // Cabinet Runs: Link common hardware products
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->foreignId('default_hinge_product_id')->nullable()->after('hardware_kit_json')
                ->constrained('products_products')->nullOnDelete();
            $table->foreignId('default_slide_product_id')->nullable()->after('default_hinge_product_id')
                ->constrained('products_products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects_doors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hinge_product_id');
            $table->dropConstrainedForeignId('decorative_hardware_product_id');
        });

        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('slide_product_id');
            $table->dropConstrainedForeignId('decorative_hardware_product_id');
        });

        Schema::table('projects_shelves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('slide_product_id');
        });

        Schema::table('projects_pullouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('slide_product_id');
        });

        Schema::table('projects_cabinets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hinge_product_id');
            $table->dropConstrainedForeignId('slide_product_id');
            $table->dropConstrainedForeignId('pullout_product_id');
            $table->dropConstrainedForeignId('lazy_susan_product_id');
        });

        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('hardware_product_id');
        });

        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_hinge_product_id');
            $table->dropConstrainedForeignId('default_slide_product_id');
        });
    }
};
