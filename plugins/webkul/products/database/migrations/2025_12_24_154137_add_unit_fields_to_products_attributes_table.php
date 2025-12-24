<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds unit configuration fields to support NUMBER and DIMENSION attribute types
     * for hardware specifications (e.g., slide length in inches, clearance in mm)
     */
    public function up(): void
    {
        Schema::table('products_attributes', function (Blueprint $table) {
            // Unit display configuration
            $table->string('unit_symbol', 10)->nullable()->after('type')
                ->comment('Unit symbol for display (e.g., in, mm, lbs)');
            $table->string('unit_label', 50)->nullable()->after('unit_symbol')
                ->comment('Full unit name (e.g., inches, millimeters, pounds)');

            // Validation constraints
            $table->decimal('min_value', 15, 4)->nullable()->after('unit_label')
                ->comment('Minimum allowed value for NUMBER/DIMENSION types');
            $table->decimal('max_value', 15, 4)->nullable()->after('min_value')
                ->comment('Maximum allowed value for NUMBER/DIMENSION types');

            // Display precision
            $table->unsignedTinyInteger('decimal_places')->default(2)->after('max_value')
                ->comment('Number of decimal places for display (0-4)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_attributes', function (Blueprint $table) {
            $table->dropColumn([
                'unit_symbol',
                'unit_label',
                'min_value',
                'max_value',
                'decimal_places',
            ]);
        });
    }
};
