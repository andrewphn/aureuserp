<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add clock in/out columns to analytic_records table for time clock functionality.
 *
 * This migration supports:
 * - Clock in/out timestamps
 * - Break/lunch duration tracking
 * - Entry type classification (timesheet, clock, manual)
 * - Manual entry approval workflow
 * - Work location tracking for clock entries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('analytic_records', function (Blueprint $table) {
            // Clock in/out timestamps
            $table->time('clock_in_time')->nullable()->after('date')
                ->comment('Time employee clocked in');
            $table->time('clock_out_time')->nullable()->after('clock_in_time')
                ->comment('Time employee clocked out');

            // Break/lunch duration in minutes (default 60 min = 1 hour lunch)
            $table->unsignedSmallInteger('break_duration_minutes')->default(60)->after('clock_out_time')
                ->comment('Break/lunch duration in minutes');

            // Entry type: how this record was created
            // - timesheet: Manual hours entry (existing behavior)
            // - clock: Clock in/out entry with timestamps
            // - manual: Retroactive entry (forgot to clock, needs approval)
            $table->enum('entry_type', ['timesheet', 'clock', 'manual'])->default('timesheet')->after('break_duration_minutes')
                ->comment('How entry was created: timesheet (hours), clock (in/out), manual (retroactive)');

            // Approval workflow for manual entries
            $table->unsignedBigInteger('approved_by')->nullable()->after('entry_type')
                ->comment('User who approved manual entry');
            $table->timestamp('approved_at')->nullable()->after('approved_by')
                ->comment('When manual entry was approved');

            // Work location for clock entries (which shop/location)
            $table->unsignedBigInteger('work_location_id')->nullable()->after('approved_at')
                ->comment('Work location where employee clocked in');

            // Notes field for manual entries or special circumstances
            $table->text('clock_notes')->nullable()->after('work_location_id')
                ->comment('Notes for manual entries or special circumstances');

            // Foreign key constraints
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('work_location_id')
                ->references('id')
                ->on('employees_work_locations')
                ->nullOnDelete();

            // Indexes for common queries
            $table->index(['user_id', 'date'], 'idx_user_date');
            $table->index(['entry_type', 'date'], 'idx_entry_type_date');
            $table->index(['work_location_id', 'date'], 'idx_location_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytic_records', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['work_location_id']);

            // Drop indexes
            $table->dropIndex('idx_user_date');
            $table->dropIndex('idx_entry_type_date');
            $table->dropIndex('idx_location_date');

            // Drop columns
            $table->dropColumn([
                'clock_in_time',
                'clock_out_time',
                'break_duration_minutes',
                'entry_type',
                'approved_by',
                'approved_at',
                'work_location_id',
                'clock_notes',
            ]);
        });
    }
};
