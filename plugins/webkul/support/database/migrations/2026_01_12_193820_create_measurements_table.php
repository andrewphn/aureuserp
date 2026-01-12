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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->string('unit_code', 20)->unique()->comment('Unit code (e.g., in, ft, yd, mm, cm, m)');
            $table->string('unit_name', 50)->comment('Full unit name (e.g., inches, feet, yards, millimeters)');
            $table->string('unit_symbol', 10)->comment('Unit symbol for display (e.g., in, ft, yd, mm, cm, m)');
            $table->string('unit_type', 20)->comment('Type: linear, area, volume, weight');
            $table->decimal('conversion_factor', 15, 8)->comment('Conversion factor to inches (e.g., 36 for yards, 25.4 for mm)');
            $table->boolean('is_base_unit')->default(false)->comment('Whether this is the base unit (inches)');
            $table->integer('display_order')->default(0)->comment('Display order in selectors');
            $table->boolean('is_active')->default(true)->comment('Whether unit is active');
            $table->text('description')->nullable()->comment('Unit description');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('unit_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
