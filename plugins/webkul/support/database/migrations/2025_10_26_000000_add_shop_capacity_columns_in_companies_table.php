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
     *
     * Adds shop capacity tracking to companies table for production planning.
     * This links to Bryan's business metrics ($84,900/month target, 68 LF/week capacity)
     * and enables capacity-based project scheduling.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Skip if columns already exist (for existing installations)
            if (!Schema::hasColumn('companies', 'shop_capacity_per_day')) {
                $table->decimal('shop_capacity_per_day', 8, 2)->nullable()
                    ->after('country_id')
                    ->comment('Linear feet production capacity per day (e.g., 13.6 LF/day)');
            }

            if (!Schema::hasColumn('companies', 'shop_capacity_per_month')) {
                $table->decimal('shop_capacity_per_month', 10, 2)->nullable()
                    ->after('shop_capacity_per_day')
                    ->comment('Linear feet production capacity per month (e.g., 272 LF/month)');
            }

            if (!Schema::hasColumn('companies', 'shop_capacity_per_hour')) {
                $table->decimal('shop_capacity_per_hour', 8, 2)->nullable()
                    ->after('shop_capacity_per_month')
                    ->comment('Linear feet production capacity per hour (e.g., 1.7 LF/hr)');
            }

            if (!Schema::hasColumn('companies', 'working_hours_per_day')) {
                $table->decimal('working_hours_per_day', 5, 2)->nullable()
                    ->after('shop_capacity_per_hour')
                    ->comment('Shop working hours per day (e.g., 8.0 hours)');
            }

            if (!Schema::hasColumn('companies', 'working_days_per_month')) {
                $table->integer('working_days_per_month')->nullable()
                    ->after('working_hours_per_day')
                    ->comment('Shop working days per month (e.g., 20 days)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'shop_capacity_per_day',
                'shop_capacity_per_month',
                'shop_capacity_per_hour',
                'working_hours_per_day',
                'working_days_per_month',
            ]);
        });
    }
};
