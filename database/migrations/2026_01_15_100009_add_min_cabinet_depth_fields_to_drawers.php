<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add minimum cabinet depth fields (Blum spec and Shop practice).
     * 
     * Blum specifies minimum inside cabinet depths per slide length:
     * - 21" slide: 21-15/16" (557mm)
     * - 18" slide: 18-29/32" (480mm)
     * - 15" slide: 15-29/32" (404mm)
     * - 12" slide: 12-29/32" (328mm)
     * - 9" slide: 10-15/32" (266mm)
     * 
     * Shop practice uses: slide length + 3/4"
     * This simpler rule works reliably in practice.
     */
    public function up(): void
    {
        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->decimal('min_cabinet_depth_blum_inches', 8, 4)->nullable()
                ->after('slide_length_inches')
                ->comment('Blum official minimum inside cabinet depth');
            
            $table->decimal('min_cabinet_depth_shop_inches', 8, 4)->nullable()
                ->after('min_cabinet_depth_blum_inches')
                ->comment('Shop minimum: slide length + 3/4 inch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->dropColumn([
                'min_cabinet_depth_blum_inches',
                'min_cabinet_depth_shop_inches',
            ]);
        });
    }
};
