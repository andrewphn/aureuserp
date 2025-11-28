<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes dimension and pricing fields nullable to support annotation workflow.
     * During annotation stage, you're just marking out cabinet runs and cabinets
     * without knowing exact measurements or pricing yet.
     *
     * These fields can be filled in later as the project progresses:
     * - Annotation stage: Just mark cabinet locations
     * - Measurement stage: Add dimensions (length, linear feet)
     * - Pricing stage: Add pricing info (unit price, total price)
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Dimensions - can be added after initial annotation
            $table->decimal('length_inches', 8, 2)->nullable()->change();
            $table->decimal('linear_feet', 8, 2)->nullable()->change();

            // Pricing - comes from product variant, can be added later
            $table->decimal('unit_price_per_lf', 10, 2)->nullable()->change();
            $table->decimal('total_price', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Revert to non-nullable (with defaults for data safety)
            $table->decimal('length_inches', 8, 2)->nullable(false)->default(0)->change();
            $table->decimal('linear_feet', 8, 2)->nullable(false)->default(0)->change();
            $table->decimal('unit_price_per_lf', 10, 2)->nullable(false)->default(0)->change();
            $table->decimal('total_price', 10, 2)->nullable(false)->default(0)->change();
        });
    }
};
