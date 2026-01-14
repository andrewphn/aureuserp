<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add hardware specification fields to products table.
     * These fields store slide/hardware specs from manufacturers (e.g., Blum Tandem).
     */
    public function up(): void
    {
        Schema::table('products_products', function (Blueprint $table) {
            // Slide-specific dimension fields
            $table->decimal('min_cabinet_depth_inches', 5, 3)
                ->nullable()
                ->after('length_inches')
                ->comment('Minimum cabinet depth required for this slide');
            
            $table->integer('weight_capacity_lbs')
                ->nullable()
                ->comment('Load capacity in pounds');
            
            $table->string('extension_type', 20)
                ->nullable()
                ->comment('full, three_quarter, over_travel');
            
            // Clearance requirements (from manufacturer specs)
            $table->decimal('top_clearance_inches', 5, 3)
                ->nullable()
                ->comment('Required clearance above drawer box');
            
            $table->decimal('bottom_clearance_inches', 5, 3)
                ->nullable()
                ->comment('Required clearance below drawer box');
            
            $table->decimal('side_clearance_inches', 5, 3)
                ->nullable()
                ->comment('Required clearance per side for slide mechanism');
            
            $table->decimal('rear_clearance_inches', 5, 3)
                ->nullable()
                ->comment('Required clearance behind drawer for rear bracket');
            
            // Linked/required products
            $table->boolean('requires_locking_device')
                ->default(false)
                ->comment('Whether this slide requires a locking device');
            
            $table->unsignedBigInteger('compatible_locking_device_id')
                ->nullable()
                ->comment('FK to compatible locking device product');
            
            $table->unsignedBigInteger('rear_bracket_product_id')
                ->nullable()
                ->comment('FK to compatible rear mounting bracket');
            
            // Foreign keys
            $table->foreign('compatible_locking_device_id')
                ->references('id')
                ->on('products_products')
                ->nullOnDelete();
            
            $table->foreign('rear_bracket_product_id')
                ->references('id')
                ->on('products_products')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_products', function (Blueprint $table) {
            $table->dropForeign(['compatible_locking_device_id']);
            $table->dropForeign(['rear_bracket_product_id']);
            
            $table->dropColumn([
                'min_cabinet_depth_inches',
                'weight_capacity_lbs',
                'extension_type',
                'top_clearance_inches',
                'bottom_clearance_inches',
                'side_clearance_inches',
                'rear_clearance_inches',
                'requires_locking_device',
                'compatible_locking_device_id',
                'rear_bracket_product_id',
            ]);
        });
    }
};
