<?php

namespace Webkul\Project\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Analytic\Models\Record;

/**
 * Timesheet Eloquent model
 *
 * Supports both traditional timesheet entries (hours) and clock in/out entries.
 *
 * Entry Types:
 * - timesheet: Manual hours entry (unit_amount = hours worked)
 * - clock: Clock in/out with timestamps (unit_amount calculated from clock times)
 * - manual: Retroactive entry requiring approval
 */
class Timesheet extends Record
{
    /**
     * Default break duration in minutes (1 hour lunch)
     */
    public const DEFAULT_BREAK_MINUTES = 60;

    /**
     * TCS shop hours (Mon-Thu 8am-5pm)
     */
    public const SHOP_OPEN_HOUR = 8;
    public const SHOP_CLOSE_HOUR = 17;
    public const SHOP_WORKING_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday'];
    public const WEEKLY_HOURS_TARGET = 32;
    /**
     * Bootstrap any application services.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($timesheet) {
            $timesheet->updateTaskTimes();
        });

        static::updated(function ($timesheet) {
            $timesheet->updateTaskTimes();
        });

        static::deleted(function ($timesheet) {
            $timesheet->updateTaskTimes();
        });
    }

    /**
     * Project
     *
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Task
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Update Task Times
     *
     */
    public function updateTaskTimes()
    {
        if (! $this->task) {
            return;
        }

        $task = $this->task;

        $effectiveHours = $hoursSpent = $task->timesheets()->sum('unit_amount');

        if ($task->subTasks->count()) {
            $hoursSpent += $task->subTasks->reduce(function ($carry, $subTask) {
                return $carry + $subTask->timesheets()->sum('unit_amount');
            }, 0);
        }

        $task->update([
            'total_hours_spent' => $hoursSpent,
            'effective_hours'   => $effectiveHours,
            'overtime'          => $hoursSpent > $task->allocated_hours ? $hoursSpent - $task->allocated_hours : 0,
            'remaining_hours'   => $task->allocated_hours - $hoursSpent,
            'progress'          => $task->allocated_hours ? ($hoursSpent / $task->allocated_hours) * 100 : 0,
        ]);

        if ($parentTask = $task->parent) {
            $parentEffectiveHours = $parentHoursSpent = $parentTask->timesheets()->sum('unit_amount');

            $parentHoursSpent += $parentTask->subTasks->reduce(function ($carry, $subTask) {
                return $carry + $subTask->timesheets()->sum('unit_amount');
            }, 0);

            $parentTask->update([
                'total_hours_spent'       => $parentHoursSpent,
                'effective_hours'         => $parentEffectiveHours,
                'subtask_effective_hours' => $parentTask->subTasks->sum('effective_hours'),
                'overtime'                => $parentHoursSpent > $parentTask->allocated_hours ? $parentHoursSpent - $parentTask->allocated_hours : 0,
                'remaining_hours'         => $parentTask->allocated_hours - $parentHoursSpent,
                'progress'                => $parentTask->allocated_hours ? ($parentHoursSpent / $parentTask->allocated_hours) * 100 : 0,
            ]);
        }
    }

    /**
     * Update Task Times Old
     *
     */
    public function updateTaskTimesOld()
    {
        if (! $this->task) {
            return;
        }

        $totalTime = $this->task->timesheets()->sum('unit_amount');

        $this->task->update([
            'total_hours_spent' => $totalTime,
            'effective_hours'   => $totalTime,
            'overtime'          => $totalTime > $this->task->allocated_hours ? $totalTime - $this->task->allocated_hours : 0,
            'remaining_hours'   => $this->task->allocated_hours - $totalTime,
            'progress'          => $this->task->allocated_hours ? ($totalTime / $this->task->allocated_hours) * 100 : 0,
        ]);
    }

    // =========================================================================
    // CLOCK IN/OUT HELPER METHODS
    // =========================================================================

    /**
     * Scope: Only clock entries (not manual timesheets)
     */
    public function scopeClockEntries(Builder $query): Builder
    {
        return $query->where('entry_type', 'clock');
    }

    /**
     * Scope: Only timesheet entries (manual hours)
     */
    public function scopeTimesheetEntries(Builder $query): Builder
    {
        return $query->where('entry_type', 'timesheet');
    }

    /**
     * Scope: Entries needing approval
     */
    public function scopeNeedsApproval(Builder $query): Builder
    {
        return $query->where('entry_type', 'manual')->whereNull('approved_by');
    }

    /**
     * Scope: Entries for a specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Entries for today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('date', Carbon::today());
    }

    /**
     * Scope: Entries for current week (Mon-Thu for TCS)
     */
    public function scopeCurrentWeek(Builder $query): Builder
    {
        $startOfWeek = Carbon::now()->startOfWeek(); // Monday
        $endOfWeek = Carbon::now()->startOfWeek()->addDays(3); // Thursday

        return $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
    }

    /**
     * Scope: Entries for a date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Check if user is currently clocked in today
     */
    public static function isUserClockedIn(int $userId): bool
    {
        return static::forUser($userId)
            ->today()
            ->clockEntries()
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->exists();
    }

    /**
     * Get user's current clock-in entry for today
     */
    public static function getCurrentClockEntry(int $userId): ?self
    {
        return static::forUser($userId)
            ->today()
            ->clockEntries()
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->first();
    }

    /**
     * Get user's total hours for today
     */
    public static function getTodayHours(int $userId): float
    {
        return static::forUser($userId)
            ->today()
            ->sum('unit_amount') ?? 0;
    }

    /**
     * Get user's total hours for current week
     */
    public static function getWeeklyHours(int $userId): float
    {
        return static::forUser($userId)
            ->currentWeek()
            ->sum('unit_amount') ?? 0;
    }

    /**
     * Get user's remaining hours to reach weekly target
     */
    public static function getRemainingWeeklyHours(int $userId): float
    {
        $worked = static::getWeeklyHours($userId);
        return max(0, self::WEEKLY_HOURS_TARGET - $worked);
    }

    /**
     * Get daily overtime (hours over 8)
     */
    public function getDailyOvertime(): float
    {
        $hours = $this->unit_amount ?? 0;
        return max(0, $hours - 8);
    }

    /**
     * Format clock time for display
     */
    public function getFormattedClockIn(): ?string
    {
        if (!$this->clock_in_time) {
            return null;
        }
        return Carbon::parse($this->clock_in_time)->format('g:i A');
    }

    /**
     * Format clock out time for display
     */
    public function getFormattedClockOut(): ?string
    {
        if (!$this->clock_out_time) {
            return null;
        }
        return Carbon::parse($this->clock_out_time)->format('g:i A');
    }

    /**
     * Get formatted break duration
     */
    public function getFormattedBreakDuration(): string
    {
        $minutes = $this->break_duration_minutes ?? self::DEFAULT_BREAK_MINUTES;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours} hour";
        }

        return "{$minutes} min";
    }

    /**
     * Get formatted working hours
     */
    public function getFormattedHours(): string
    {
        $hours = $this->unit_amount ?? 0;
        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);

        if ($minutes > 0) {
            return "{$wholeHours}h {$minutes}m";
        }

        return "{$wholeHours} hours";
    }

    /**
     * Check if clock-in time is late (after 8:15 AM)
     */
    public function isLateArrival(): bool
    {
        if (!$this->clock_in_time) {
            return false;
        }

        $clockIn = Carbon::parse($this->clock_in_time);
        $lateThreshold = Carbon::today()->setHour(self::SHOP_OPEN_HOUR)->setMinute(15);

        return $clockIn->gt($lateThreshold);
    }

    /**
     * Get summary for display
     */
    public function getClockSummary(): array
    {
        return [
            'date' => $this->date->format('M j, Y'),
            'day' => $this->date->format('l'),
            'clock_in' => $this->getFormattedClockIn(),
            'clock_out' => $this->getFormattedClockOut(),
            'break' => $this->getFormattedBreakDuration(),
            'hours' => $this->getFormattedHours(),
            'hours_decimal' => $this->unit_amount,
            'overtime' => $this->getDailyOvertime(),
            'is_late' => $this->isLateArrival(),
            'entry_type' => $this->entry_type,
            'needs_approval' => $this->needsApproval(),
            'project' => $this->project?->name,
        ];
    }
}
