<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\WorkLocation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Timesheet;

/**
 * Clocking Service
 *
 * Handles all clock in/out business logic for the time clock system.
 * Supports TCS Newburgh Shop schedule: Mon-Thu 8am-5pm with 1-hour lunch.
 */
class ClockingService
{
    /**
     * Clock in an employee
     */
    public function clockIn(
        int $userId,
        ?int $workLocationId = null,
        ?string $notes = null
    ): array {
        try {
            // Check if already clocked in today
            if (Timesheet::isUserClockedIn($userId)) {
                $existing = Timesheet::getCurrentClockEntry($userId);
                return [
                    'success' => false,
                    'error' => 'Already clocked in today',
                    'clock_in_time' => $existing?->getFormattedClockIn(),
                ];
            }

            // Get employee's default work location if not provided
            if (!$workLocationId) {
                $employee = Employee::where('user_id', $userId)->first();
                $workLocationId = $employee?->work_location_id;
            }

            // Create clock entry
            $entry = Timesheet::create([
                'type' => 'projects', // Required for timesheet type
                'date' => Carbon::today(),
                'clock_in_time' => Carbon::now()->format('H:i:s'),
                'entry_type' => 'clock',
                'user_id' => $userId,
                'work_location_id' => $workLocationId,
                'clock_notes' => $notes,
                'break_duration_minutes' => Timesheet::DEFAULT_BREAK_MINUTES,
                'company_id' => $this->getUserCompanyId($userId),
                'creator_id' => $userId,
                'unit_amount' => 0, // Will be calculated on clock out
            ]);

            Log::info('ClockingService: Employee clocked in', [
                'user_id' => $userId,
                'entry_id' => $entry->id,
                'clock_in_time' => $entry->clock_in_time,
            ]);

            return [
                'success' => true,
                'message' => 'Clocked in successfully',
                'entry_id' => $entry->id,
                'clock_in_time' => $entry->getFormattedClockIn(),
                'date' => $entry->date->format('l, M j'),
                'work_location' => $entry->workLocation?->name,
            ];
        } catch (\Exception $e) {
            Log::error('ClockingService: Clock in failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to clock in: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Clock out an employee
     */
    public function clockOut(
        int $userId,
        int $breakDurationMinutes = 60,
        ?int $projectId = null,
        ?string $notes = null
    ): array {
        try {
            // Get current clock entry
            $entry = Timesheet::getCurrentClockEntry($userId);

            if (!$entry) {
                return [
                    'success' => false,
                    'error' => 'Not currently clocked in',
                ];
            }

            // Calculate working hours
            $clockIn = Carbon::parse($entry->clock_in_time);
            $clockOut = Carbon::now();

            $totalMinutes = $clockIn->diffInMinutes($clockOut, false);
            $workingMinutes = $totalMinutes - $breakDurationMinutes;
            $workingHours = round($workingMinutes / 60, 2);

            // Ensure positive hours
            if ($workingHours < 0) {
                $workingHours = 0;
            }

            // Update entry
            $entry->update([
                'clock_out_time' => $clockOut->format('H:i:s'),
                'break_duration_minutes' => $breakDurationMinutes,
                'unit_amount' => $workingHours,
                'project_id' => $projectId,
                'clock_notes' => $notes ? ($entry->clock_notes ? $entry->clock_notes . "\n" . $notes : $notes) : $entry->clock_notes,
            ]);

            // Get weekly totals
            $weeklyHours = Timesheet::getWeeklyHours($userId);
            $remainingHours = Timesheet::getRemainingWeeklyHours($userId);

            Log::info('ClockingService: Employee clocked out', [
                'user_id' => $userId,
                'entry_id' => $entry->id,
                'hours_worked' => $workingHours,
                'weekly_total' => $weeklyHours,
            ]);

            return [
                'success' => true,
                'message' => 'Clocked out successfully',
                'entry_id' => $entry->id,
                'clock_in_time' => $entry->getFormattedClockIn(),
                'clock_out_time' => $entry->getFormattedClockOut(),
                'break_duration' => $entry->getFormattedBreakDuration(),
                'hours_worked' => $workingHours,
                'hours_formatted' => $entry->getFormattedHours(),
                'overtime' => $entry->getDailyOvertime(),
                'weekly_hours' => $weeklyHours,
                'remaining_hours' => $remainingHours,
                'project' => $entry->project?->name,
            ];
        } catch (\Exception $e) {
            Log::error('ClockingService: Clock out failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to clock out: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Start lunch break for an employee
     */
    public function startLunch(int $userId): array
    {
        try {
            // Get current clock entry
            $entry = Timesheet::getCurrentClockEntry($userId);

            if (!$entry) {
                return [
                    'success' => false,
                    'message' => 'Not currently clocked in',
                ];
            }

            // Check if already on lunch
            if ($entry->lunch_start_time && !$entry->lunch_end_time) {
                return [
                    'success' => false,
                    'message' => 'Already on lunch break',
                ];
            }

            // Check if lunch was already taken
            if ($entry->lunch_start_time && $entry->lunch_end_time) {
                return [
                    'success' => false,
                    'message' => 'Lunch break already taken today',
                ];
            }

            // Start lunch
            $entry->update([
                'lunch_start_time' => Carbon::now()->format('H:i:s'),
            ]);

            Log::info('ClockingService: Employee started lunch', [
                'user_id' => $userId,
                'entry_id' => $entry->id,
                'lunch_start_time' => $entry->lunch_start_time,
            ]);

            return [
                'success' => true,
                'message' => 'Lunch break started',
                'lunch_start_time' => Carbon::parse($entry->lunch_start_time)->format('g:i A'),
            ];
        } catch (\Exception $e) {
            Log::error('ClockingService: Start lunch failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to start lunch: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * End lunch break for an employee
     */
    public function endLunch(int $userId): array
    {
        try {
            // Get current clock entry
            $entry = Timesheet::getCurrentClockEntry($userId);

            if (!$entry) {
                return [
                    'success' => false,
                    'message' => 'Not currently clocked in',
                ];
            }

            // Check if lunch was started
            if (!$entry->lunch_start_time) {
                return [
                    'success' => false,
                    'message' => 'Lunch break was not started',
                ];
            }

            // Check if already ended lunch
            if ($entry->lunch_end_time) {
                return [
                    'success' => false,
                    'message' => 'Already returned from lunch',
                ];
            }

            // End lunch and calculate duration
            $lunchStart = Carbon::parse($entry->lunch_start_time);
            $lunchEnd = Carbon::now();
            $lunchDurationMinutes = $lunchEnd->diffInMinutes($lunchStart);

            $entry->update([
                'lunch_end_time' => $lunchEnd->format('H:i:s'),
                'break_duration_minutes' => $lunchDurationMinutes,
            ]);

            Log::info('ClockingService: Employee ended lunch', [
                'user_id' => $userId,
                'entry_id' => $entry->id,
                'lunch_end_time' => $entry->lunch_end_time,
                'lunch_duration_minutes' => $lunchDurationMinutes,
            ]);

            return [
                'success' => true,
                'message' => 'Welcome back from lunch!',
                'lunch_end_time' => Carbon::parse($entry->lunch_end_time)->format('g:i A'),
                'lunch_duration_minutes' => $lunchDurationMinutes,
            ];
        } catch (\Exception $e) {
            Log::error('ClockingService: End lunch failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to end lunch: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get current clock status for a user
     */
    public function getStatus(int $userId): array
    {
        $isClockedIn = Timesheet::isUserClockedIn($userId);
        $currentEntry = $isClockedIn ? Timesheet::getCurrentClockEntry($userId) : null;

        $todayHours = Timesheet::getTodayHours($userId);
        $weeklyHours = Timesheet::getWeeklyHours($userId);
        $remainingHours = Timesheet::getRemainingWeeklyHours($userId);

        // Calculate running time if clocked in
        $runningHours = 0;
        if ($currentEntry) {
            $clockIn = Carbon::parse($currentEntry->clock_in_time);
            $runningMinutes = Carbon::now()->diffInMinutes($clockIn);
            $runningHours = round($runningMinutes / 60, 2);
        }

        // Check lunch status
        $isOnLunch = false;
        $lunchStartTime = null;
        $lunchEndTime = null;
        $lunchTaken = false;

        if ($currentEntry) {
            $isOnLunch = $currentEntry->lunch_start_time && !$currentEntry->lunch_end_time;
            $lunchStartTime = $currentEntry->lunch_start_time
                ? Carbon::parse($currentEntry->lunch_start_time)->format('g:i A')
                : null;
            $lunchEndTime = $currentEntry->lunch_end_time
                ? Carbon::parse($currentEntry->lunch_end_time)->format('g:i A')
                : null;
            $lunchTaken = $currentEntry->lunch_start_time && $currentEntry->lunch_end_time;
        }

        return [
            'is_clocked_in' => $isClockedIn,
            'clock_in_time' => $currentEntry?->getFormattedClockIn(),
            'clock_in_timestamp' => $currentEntry?->clock_in_time ? Carbon::parse($currentEntry->clock_in_time)->toIso8601String() : null,
            'running_hours' => $runningHours,
            'today_hours' => $todayHours,
            'weekly_hours' => $weeklyHours,
            'remaining_hours' => $remainingHours,
            'weekly_target' => Timesheet::WEEKLY_HOURS_TARGET,
            'on_track' => $this->isOnTrack($userId),
            'current_entry_id' => $currentEntry?->id,
            'work_location' => $currentEntry?->workLocation?->name,
            'is_on_lunch' => $isOnLunch,
            'lunch_start_time' => $lunchStartTime,
            'lunch_start_timestamp' => $currentEntry?->lunch_start_time ? Carbon::parse($currentEntry->lunch_start_time)->toIso8601String() : null,
            'lunch_end_time' => $lunchEndTime,
            'lunch_taken' => $lunchTaken,
        ];
    }

    /**
     * Add a manual time entry (for missed punches)
     */
    public function addManualEntry(
        int $userId,
        string $date,
        string $clockInTime,
        string $clockOutTime,
        int $breakDurationMinutes = 60,
        ?int $projectId = null,
        ?string $notes = null
    ): array {
        try {
            $dateCarbon = Carbon::parse($date);
            $clockIn = Carbon::parse($clockInTime);
            $clockOut = Carbon::parse($clockOutTime);

            // Validate times
            if ($clockOut->lte($clockIn)) {
                return [
                    'success' => false,
                    'error' => 'Clock out time must be after clock in time',
                ];
            }

            // Calculate hours
            $totalMinutes = $clockOut->diffInMinutes($clockIn);
            $workingMinutes = $totalMinutes - $breakDurationMinutes;
            $workingHours = round($workingMinutes / 60, 2);

            // Check for existing entry on this date
            $existingEntry = Timesheet::forUser($userId)
                ->whereDate('date', $dateCarbon)
                ->clockEntries()
                ->first();

            if ($existingEntry) {
                return [
                    'success' => false,
                    'error' => 'Entry already exists for this date',
                    'existing_entry' => $existingEntry->getClockSummary(),
                ];
            }

            // Determine if approval is needed (entries > 24 hours old)
            $needsApproval = $dateCarbon->lt(Carbon::today()->subDay());
            $entryType = $needsApproval ? 'manual' : 'clock';

            // Create entry
            $entry = Timesheet::create([
                'type' => 'projects',
                'date' => $dateCarbon,
                'clock_in_time' => $clockIn->format('H:i:s'),
                'clock_out_time' => $clockOut->format('H:i:s'),
                'break_duration_minutes' => $breakDurationMinutes,
                'entry_type' => $entryType,
                'user_id' => $userId,
                'project_id' => $projectId,
                'company_id' => $this->getUserCompanyId($userId),
                'creator_id' => auth()->id() ?? $userId,
                'unit_amount' => $workingHours,
                'clock_notes' => $notes ?? 'Manual entry' . ($needsApproval ? ' (pending approval)' : ''),
            ]);

            Log::info('ClockingService: Manual entry created', [
                'user_id' => $userId,
                'entry_id' => $entry->id,
                'date' => $date,
                'needs_approval' => $needsApproval,
            ]);

            return [
                'success' => true,
                'message' => $needsApproval
                    ? 'Manual entry created and flagged for supervisor approval'
                    : 'Time entry created successfully',
                'entry_id' => $entry->id,
                'needs_approval' => $needsApproval,
                'summary' => $entry->getClockSummary(),
            ];
        } catch (\Exception $e) {
            Log::error('ClockingService: Manual entry failed', [
                'user_id' => $userId,
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create manual entry: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Approve a manual entry
     */
    public function approveEntry(int $entryId, int $approverUserId): array
    {
        try {
            $entry = Timesheet::find($entryId);

            if (!$entry) {
                return ['success' => false, 'error' => 'Entry not found'];
            }

            if (!$entry->needsApproval()) {
                return ['success' => false, 'error' => 'Entry does not need approval'];
            }

            $entry->update([
                'approved_by' => $approverUserId,
                'approved_at' => Carbon::now(),
            ]);

            Log::info('ClockingService: Entry approved', [
                'entry_id' => $entryId,
                'approver_id' => $approverUserId,
            ]);

            return [
                'success' => true,
                'message' => 'Entry approved successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to approve entry: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get weekly hours summary for a user
     */
    public function getWeeklySummary(int $userId, ?Carbon $weekStart = null): array
    {
        $startOfWeek = $weekStart ?? Carbon::now()->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(3); // Mon-Thu

        $entries = Timesheet::forUser($userId)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->orderBy('date')
            ->get();

        $dailyHours = [];
        $totalHours = 0;
        $totalOvertime = 0;

        foreach (['monday', 'tuesday', 'wednesday', 'thursday'] as $index => $day) {
            $date = $startOfWeek->copy()->addDays($index);
            $dayEntries = $entries->filter(fn($e) => $e->date->isSameDay($date));
            $hours = $dayEntries->sum('unit_amount');

            $dailyHours[$day] = [
                'date' => $date->format('M j'),
                'hours' => $hours,
                'formatted' => $this->formatHours($hours),
                'overtime' => max(0, $hours - 8),
                'entries' => $dayEntries->map(fn($e) => $e->getClockSummary())->values(),
            ];

            $totalHours += $hours;
            $totalOvertime += max(0, $hours - 8);
        }

        return [
            'week_start' => $startOfWeek->format('M j'),
            'week_end' => $endOfWeek->format('M j'),
            'daily' => $dailyHours,
            'total_hours' => $totalHours,
            'total_formatted' => $this->formatHours($totalHours),
            'total_overtime' => $totalOvertime,
            'target' => Timesheet::WEEKLY_HOURS_TARGET,
            'remaining' => max(0, Timesheet::WEEKLY_HOURS_TARGET - $totalHours),
            'on_track' => $totalHours >= $this->getExpectedHoursToDate($startOfWeek),
        ];
    }

    /**
     * Get all employees' attendance for today (for owner dashboard)
     */
    public function getTodayAttendance(): array
    {
        $employees = Employee::with(['user', 'workLocation'])
            ->where('is_active', true)
            ->get();

        $attendance = [];

        foreach ($employees as $employee) {
            if (!$employee->user_id) {
                continue;
            }

            $status = $this->getStatus($employee->user_id);
            $attendance[] = [
                'employee_id' => $employee->id,
                'user_id' => $employee->user_id,
                'name' => $employee->name,
                'is_clocked_in' => $status['is_clocked_in'],
                'clock_in_time' => $status['clock_in_time'],
                'running_hours' => $status['running_hours'],
                'today_hours' => $status['today_hours'],
                'work_location' => $status['work_location'] ?? $employee->workLocation?->name,
                'is_late' => $status['is_clocked_in'] && $this->isLateClockIn($status['clock_in_time']),
            ];
        }

        // Sort: clocked in first, then by name
        usort($attendance, function ($a, $b) {
            if ($a['is_clocked_in'] !== $b['is_clocked_in']) {
                return $b['is_clocked_in'] - $a['is_clocked_in'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return [
            'date' => Carbon::today()->format('l, M j, Y'),
            'employees' => $attendance,
            'total_clocked_in' => collect($attendance)->where('is_clocked_in', true)->count(),
            'total_employees' => count($attendance),
        ];
    }

    /**
     * Assign time to project for an existing entry
     */
    public function assignToProject(int $entryId, int $projectId): array
    {
        try {
            $entry = Timesheet::find($entryId);

            if (!$entry) {
                return ['success' => false, 'error' => 'Entry not found'];
            }

            $project = Project::find($projectId);
            if (!$project) {
                return ['success' => false, 'error' => 'Project not found'];
            }

            $entry->update(['project_id' => $projectId]);

            return [
                'success' => true,
                'message' => "Time assigned to {$project->name}",
                'project' => $project->name,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to assign project: ' . $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Get user's company ID
     */
    private function getUserCompanyId(int $userId): ?int
    {
        $employee = Employee::where('user_id', $userId)->first();
        return $employee?->company_id ?? 1; // Default to TCS company
    }

    /**
     * Format hours for display
     */
    private function formatHours(float $hours): string
    {
        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);

        if ($minutes > 0) {
            return "{$wholeHours}h {$minutes}m";
        }

        return "{$wholeHours} hours";
    }

    /**
     * Check if employee is on track for weekly hours
     */
    private function isOnTrack(int $userId): bool
    {
        $weeklyHours = Timesheet::getWeeklyHours($userId);
        $expectedHours = $this->getExpectedHoursToDate();

        return $weeklyHours >= ($expectedHours * 0.9); // Within 10% of expected
    }

    /**
     * Get expected hours to date based on day of week
     */
    private function getExpectedHoursToDate(?Carbon $weekStart = null): float
    {
        $today = Carbon::today();
        $startOfWeek = $weekStart ?? Carbon::now()->startOfWeek();

        // Days worked so far (Mon=0, Thu=3)
        $dayOfWeek = min($today->diffInDays($startOfWeek), 3);

        // 8 hours per day
        return ($dayOfWeek + 1) * 8;
    }

    /**
     * Check if clock in time is late (after 8:15 AM)
     */
    private function isLateClockIn(?string $clockInTime): bool
    {
        if (!$clockInTime) {
            return false;
        }

        // Parse time like "8:02 AM"
        try {
            $clockIn = Carbon::parse($clockInTime);
            $lateThreshold = Carbon::today()->setHour(8)->setMinute(15);
            return $clockIn->gt($lateThreshold);
        } catch (\Exception $e) {
            return false;
        }
    }
}
