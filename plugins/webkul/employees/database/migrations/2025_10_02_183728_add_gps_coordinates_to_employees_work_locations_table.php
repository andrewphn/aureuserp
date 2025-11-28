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
        Schema::table('employees_work_locations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('location_number')->comment('GPS Latitude');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('GPS Longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees_work_locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
