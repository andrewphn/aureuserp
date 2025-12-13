<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\Timesheet;
use Webkul\Security\Models\User;

class ProductivityService
{
    /**
     * Get tasks completed during a specific clock session
     *
     * @param int $userId
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @return Collection
     */
    public function getTasksCompletedDuringShift(int $userId, Carbon $clockIn, Carbon $clockOut): Collection
    {
        return Task::query()
            ->where('completed_by', $userId)
            ->whereBetween('completed_at', [$clockIn, $clockOut])
            ->with(['project:id,name', 'room:id,name'])
            ->orderBy('completed_at')
            ->get();
    }

    /**
     * Get tasks started during a specific clock session
     *
     * @param int $userId
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @return Collection
     */
    public function getTasksStartedDuringShift(int $userId, Carbon $clockIn, Carbon $clockOut): Collection
    {
        return Task::query()
            ->where('started_by', $userId)
            ->whereBetween('started_at', [$clockIn, $clockOut])
            ->with(['project:id,name', 'room:id,name'])
            ->orderBy('started_at')
            ->get();
    }

    /**
     * Get daily productivity summary for a user
     *
     * @param int $userId
     * @param Carbon|null $date
     * @return array
     */
    public function getDailyProductivity(int $userId, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Get clock entries for the day
        $clockEntries = Timesheet::query()
            ->where('user_id', $userId)
            ->whereDate('date', $date)
            ->where('entry_type', 'clock')
            ->orderBy('clock_in_time')
            ->get();

        // Get tasks completed today
        $tasksCompleted = Task::query()
            ->where('completed_by', $userId)
            ->whereBetween('completed_at', [$startOfDay, $endOfDay])
            ->with(['project:id,name'])
            ->get();

        // Get tasks started today
        $tasksStarted = Task::query()
            ->where('started_by', $userId)
            ->whereBetween('started_at', [$startOfDay, $endOfDay])
            ->with(['project:id,name'])
            ->get();

        // Calculate hours worked
        $totalHours = $clockEntries->sum(function ($entry) {
            if (!$entry->clock_in_time || !$entry->clock_out_time) {
                return 0;
            }
            // clock_in_time and clock_out_time are already Carbon instances
            $breakMinutes = $entry->break_duration_minutes ?? 0;
            return max(0, $entry->clock_out_time->diffInMinutes($entry->clock_in_time) - $breakMinutes) / 60;
        });

        return [
            'date' => $date->format('Y-m-d'),
            'user_id' => $userId,
            'hours_worked' => round($totalHours, 2),
            'tasks_completed' => $tasksCompleted->count(),
            'tasks_started' => $tasksStarted->count(),
            'completed_tasks' => $tasksCompleted->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'project' => $t->project?->name,
                'completed_at' => $t->completed_at->format('g:i A'),
            ])->toArray(),
            'started_tasks' => $tasksStarted->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'project' => $t->project?->name,
                'started_at' => $t->started_at->format('g:i A'),
            ])->toArray(),
            'clock_entries' => $clockEntries->map(fn($e) => [
                'clock_in' => $e->getFormattedClockIn(),
                'clock_out' => $e->getFormattedClockOut(),
                'break_minutes' => $e->break_duration_minutes,
            ])->toArray(),
        ];
    }

    /**
     * Get weekly productivity summary for a user
     *
     * @param int $userId
     * @param Carbon|null $weekStart
     * @return array
     */
    public function getWeeklyProductivity(int $userId, ?Carbon $weekStart = null): array
    {
        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $dailySummaries = [];
        $totalHours = 0;
        $totalTasksCompleted = 0;
        $totalTasksStarted = 0;

        for ($day = $weekStart->copy(); $day <= $weekEnd; $day->addDay()) {
            $daily = $this->getDailyProductivity($userId, $day->copy());
            $dailySummaries[$day->format('l')] = $daily;
            $totalHours += $daily['hours_worked'];
            $totalTasksCompleted += $daily['tasks_completed'];
            $totalTasksStarted += $daily['tasks_started'];
        }

        return [
            'user_id' => $userId,
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'total_hours' => round($totalHours, 2),
            'total_tasks_completed' => $totalTasksCompleted,
            'total_tasks_started' => $totalTasksStarted,
            'tasks_per_hour' => $totalHours > 0 ? round($totalTasksCompleted / $totalHours, 2) : 0,
            'daily_breakdown' => $dailySummaries,
        ];
    }

    /**
     * Get team productivity for a day
     *
     * @param Carbon|null $date
     * @return array
     */
    public function getTeamProductivity(?Carbon $date = null): array
    {
        $date = $date ?? now();

        // Get all users who clocked in today
        $userIds = Timesheet::query()
            ->whereDate('date', $date)
            ->where('entry_type', 'clock')
            ->distinct()
            ->pluck('user_id');

        $teamData = [];
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            $daily = $this->getDailyProductivity($userId, $date);
            $teamData[] = [
                'user_id' => $userId,
                'name' => $user->name,
                'hours_worked' => $daily['hours_worked'],
                'tasks_completed' => $daily['tasks_completed'],
                'tasks_started' => $daily['tasks_started'],
            ];
        }

        // Sort by tasks completed desc
        usort($teamData, fn($a, $b) => $b['tasks_completed'] <=> $a['tasks_completed']);

        return [
            'date' => $date->format('Y-m-d'),
            'team_size' => count($teamData),
            'total_hours' => round(array_sum(array_column($teamData, 'hours_worked')), 2),
            'total_tasks_completed' => array_sum(array_column($teamData, 'tasks_completed')),
            'members' => $teamData,
        ];
    }
}
