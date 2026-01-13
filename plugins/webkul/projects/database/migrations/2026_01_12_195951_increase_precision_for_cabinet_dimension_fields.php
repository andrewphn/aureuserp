<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Increase precision of dimension fields to support fractional measurements.
     * Changes from decimal(8,2) to decimal(10,4) to preserve precision for
     * fractional inputs like "41-5/16" (41.3125 inches).
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Increase precision to support fractional measurements (e.g., 5/16 = 0.3125)
            // decimal(10,4) allows up to 999999.9999 inches with 4 decimal precision
            $table->decimal('length_inches', 10, 4)->nullable()->change();
            $table->decimal('width_inches', 10, 4)->nullable()->change();
            $table->decimal('depth_inches', 10, 4)->nullable()->change();
            $table->decimal('height_inches', 10, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Revert to original precision
            $table->decimal('length_inches', 8, 2)->nullable()->change();
            $table->decimal('width_inches', 8, 2)->nullable()->change();
            $table->decimal('depth_inches', 8, 2)->nullable()->change();
            $table->decimal('height_inches', 8, 2)->nullable()->change();
        });
    }
};
