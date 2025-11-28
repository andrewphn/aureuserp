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
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('shop_capacity_per_day', 10, 2)->nullable()->comment('Linear feet production capacity per day');
            $table->decimal('shop_capacity_per_month', 10, 2)->nullable()->after('shop_capacity_per_day')->comment('Linear feet production capacity per month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['shop_capacity_per_day', 'shop_capacity_per_month']);
        });
    }
};
