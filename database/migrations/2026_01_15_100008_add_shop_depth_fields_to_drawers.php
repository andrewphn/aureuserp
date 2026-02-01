<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add shop depth fields to drawers table.
     * 
     * Shop depths are nominal slide length + 1/4" for safety clearance.
     * This is standard shop practice to ensure proper slide operation.
     * 
     * Example: 18" slide → 18.25" (18-1/4") shop depth
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('projects_cabinet_drawers')) {
            return;
        }

Schema::table('projects_drawers', function (Blueprint $table) {
            // Shop depth - slide length + 1/4" for safety
            $table->decimal('box_depth_shop_inches', 8, 4)->nullable()
                ->after('box_depth_inches')
                ->comment('Shop depth: nominal slide length + 1/4 inch');
            
            // Side cut length (shop version - this is the depth dimension for sides)
            $table->decimal('side_cut_length_shop_inches', 8, 4)->nullable()
                ->after('side_cut_length_inches')
                ->comment('Side piece shop length: slide length + 1/4 inch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->dropColumn([
                'box_depth_shop_inches',
                'side_cut_length_shop_inches',
            ]);
        });
    }
};
