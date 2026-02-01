<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add lunch start/end time columns for real-time lunch tracking.
 *
 * This allows employees to actively record when they go to lunch
 * and when they return, rather than selecting a duration at clock out.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('analytic_records')) {
            return;
        }

        // Skip if clock_out_time doesn't exist (previous migration was skipped)
        if (!Schema::hasColumn('analytic_records', 'clock_out_time')) {
            return;
        }

        Schema::table('analytic_records', function (Blueprint $table) {
            // Lunch start/end timestamps for real-time tracking
            $table->time('lunch_start_time')->nullable()->after('clock_out_time')
                ->comment('Time employee started lunch break');
            $table->time('lunch_end_time')->nullable()->after('lunch_start_time')
                ->comment('Time employee returned from lunch break');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('analytic_records')) {
            return;
        }

        Schema::table('analytic_records', function (Blueprint $table) {
            $table->dropColumn([
                'lunch_start_time',
                'lunch_end_time',
            ]);
        });
    }
};
