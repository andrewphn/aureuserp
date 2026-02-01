<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add shop height fields to drawers table.
     * 
     * Shop heights are theoretical heights rounded DOWN to nearest 1/2" for safety.
     * This is a common woodworking practice to ensure drawers fit with clearance.
     * 
     * Example: 5.1875" (5-3/16") theoretical → 5.0" (5") shop height
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_cabinet_drawers')) {
            return;
        }

Schema::table('projects_drawers', function (Blueprint $table) {
            // Shop heights - rounded down to nearest 1/2" for safety
            $table->decimal('box_height_shop_inches', 8, 4)->nullable()
                ->after('box_height_inches')
                ->comment('Shop height: theoretical rounded DOWN to nearest 1/2 inch');
            
            // Side cut heights (shop version)
            $table->decimal('side_cut_height_shop_inches', 8, 4)->nullable()
                ->after('side_cut_height_inches')
                ->comment('Side piece shop height: rounded DOWN to nearest 1/2 inch');
            
            // Front cut heights (shop version)  
            $table->decimal('front_cut_height_shop_inches', 8, 4)->nullable()
                ->after('front_cut_height_inches')
                ->comment('Front piece shop height: rounded DOWN to nearest 1/2 inch');
            
            // Back cut heights (shop version)
            $table->decimal('back_cut_height_shop_inches', 8, 4)->nullable()
                ->after('back_cut_height_inches')
                ->comment('Back piece shop height: rounded DOWN to nearest 1/2 inch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->dropColumn([
                'box_height_shop_inches',
                'side_cut_height_shop_inches',
                'front_cut_height_shop_inches',
                'back_cut_height_shop_inches',
            ]);
        });
    }
};
