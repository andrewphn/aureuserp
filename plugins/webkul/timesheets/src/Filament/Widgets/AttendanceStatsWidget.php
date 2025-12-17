<?php

namespace Webkul\Timesheet\Filament\Widgets;

use App\Services\ClockingService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Project\Models\Timesheet;

/**
 * Attendance Stats Widget
 *
 * Shows real-time attendance stats for the owner dashboard:
 * - Employees currently clocked in
 * - Total hours worked today (all employees)
 * - Entries pending approval
 */
class AttendanceStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    /**
     * Determine if the widget can be viewed.
     */
    public static function canView(): bool
    {
        return true;
    }

    protected function getHeading(): ?string
    {
        return 'Today\'s Attendance - ' . now()->format('l, M j');
    }

    protected function getStats(): array
    {
        $clockingService = app(ClockingService::class);
        $attendance = $clockingService->getTodayAttendance();

        $clockedInCount = $attendance['total_clocked_in'];
        $totalEmployees = $attendance['total_employees'];

        // Calculate total hours worked today
        $totalHoursToday = Timesheet::query()
            ->whereDate('date', today())
            ->sum('unit_amount');

        // Get running hours (clocked in but not yet clocked out)
        $runningHours = collect($attendance['employees'])
            ->where('is_clocked_in', true)
            ->sum('running_hours');

        // Get entries needing approval
        $pendingApproval = Timesheet::needsApproval()->count();

        // Get late arrivals today
        $lateArrivals = collect($attendance['employees'])
            ->where('is_late', true)
            ->count();

        return [
            Stat::make('Clocked In', "{$clockedInCount} / {$totalEmployees}")
                ->description($clockedInCount === $totalEmployees ? 'All present' : ($totalEmployees - $clockedInCount) . ' not in yet')
                ->descriptionIcon($clockedInCount === $totalEmployees ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($clockedInCount === $totalEmployees ? 'success' : 'warning'),

            Stat::make('Hours Today', $this->formatHours($totalHoursToday + $runningHours))
                ->description($runningHours > 0 ? 'Includes ' . $this->formatHours($runningHours) . ' running' : 'Completed hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Pending Approval', $pendingApproval)
                ->description($pendingApproval > 0 ? 'Manual entries need review' : 'All entries approved')
                ->descriptionIcon($pendingApproval > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($pendingApproval > 0 ? 'danger' : 'success'),

            Stat::make('Late Arrivals', $lateArrivals)
                ->description($lateArrivals === 0 ? 'All on time' : 'After 8:15 AM')
                ->descriptionIcon($lateArrivals === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($lateArrivals === 0 ? 'success' : 'warning'),
        ];
    }

    /**
     * Format hours for display
     */
    protected function formatHours(float $hours): string
    {
        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);

        if ($hours === 0.0) {
            return '0h';
        }

        if ($minutes > 0) {
            return "{$wholeHours}h {$minutes}m";
        }

        return "{$wholeHours}h";
    }
}
