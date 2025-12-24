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
        Schema::table('projects_cabinets', function (Blueprint $table) {
            // Line item adjustment fields
            $table->string('adjustment_type', 20)->default('none')->after('total_price')
                ->comment('none, discount_fixed, discount_percent, markup_fixed, markup_percent');
            $table->decimal('adjustment_value', 10, 2)->nullable()->after('adjustment_type');
            $table->string('adjustment_reason', 255)->nullable()->after('adjustment_value');
            $table->decimal('adjustment_amount', 10, 2)->nullable()->after('adjustment_reason')
                ->comment('Calculated adjustment amount in dollars');
            $table->decimal('final_price', 10, 2)->nullable()->after('adjustment_amount')
                ->comment('Total price after adjustment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinets', function (Blueprint $table) {
            $table->dropColumn([
                'adjustment_type',
                'adjustment_value',
                'adjustment_reason',
                'adjustment_amount',
                'final_price',
            ]);
        });
    }
};
