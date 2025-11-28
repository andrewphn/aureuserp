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
            $table->decimal('shop_capacity_per_hour', 10, 2)->nullable()->after('shop_capacity_per_month');
            $table->decimal('working_hours_per_day', 10, 2)->nullable()->after('shop_capacity_per_hour');
            $table->integer('working_days_per_month')->nullable()->after('working_hours_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['shop_capacity_per_hour', 'working_hours_per_day', 'working_days_per_month']);
        });
    }
};
