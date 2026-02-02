<?php

namespace Webkul\Project\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Project\Services\CncCapacityAnalyticsService;

/**
 * CNC Capacity Widget
 *
 * Displays CNC production capacity metrics including daily, weekly,
 * and monthly board feet production calculated from actual VCarve data.
 */
class CncCapacityWidget extends BaseWidget
{
    use HasWidgetShield;

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    protected function getHeading(): ?string
    {
        return 'CNC Board Feet Capacity';
    }

    protected function getDescription(): ?string
    {
        return 'Production capacity based on VCarve sheet data';
    }

    protected function getStats(): array
    {
        $service = app(CncCapacityAnalyticsService::class);
        $stats = $service->getDashboardStats();

        $todayBf = $stats['today']['board_feet'];
        $todaySheets = $stats['today']['sheets'];

        $weekBf = $stats['this_week']['board_feet'];
        $weekSheets = $stats['this_week']['sheets'];
        $weekAvg = $stats['this_week']['avg_per_day'];

        $monthBf = $stats['this_month']['board_feet'];
        $monthSheets = $stats['this_month']['sheets'];
        $monthAvg = $stats['this_month']['avg_per_day'];

        $thirtyDayAvg = $stats['thirty_day_avg']['bf_per_day'];
        $thirtyDaySheetAvg = $stats['thirty_day_avg']['sheets_per_day'];

        // Determine trend direction
        $weekVsAvg = $thirtyDayAvg > 0 ? ($weekAvg / $thirtyDayAvg) - 1 : 0;
        $weekTrend = $weekVsAvg > 0.05 ? 'up' : ($weekVsAvg < -0.05 ? 'down' : 'neutral');

        return [
            Stat::make('Today', $this->formatBoardFeet($todayBf))
                ->description("{$todaySheets} sheets")
                ->icon('heroicon-o-calendar')
                ->color($todaySheets > 0 ? 'success' : 'gray'),

            Stat::make('This Week', $this->formatBoardFeet($weekBf))
                ->description("{$weekSheets} sheets ({$this->formatBoardFeet($weekAvg)}/day)")
                ->icon('heroicon-o-chart-bar')
                ->color($this->getWeekColor($weekAvg, $thirtyDayAvg)),

            Stat::make('This Month', $this->formatBoardFeet($monthBf))
                ->description("{$monthSheets} sheets ({$this->formatBoardFeet($monthAvg)}/day)")
                ->icon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('30-Day Average', $this->formatBoardFeet($thirtyDayAvg) . '/day')
                ->description("{$thirtyDaySheetAvg} sheets/day")
                ->icon('heroicon-o-arrow-trending-up')
                ->color('primary'),
        ];
    }

    protected function formatBoardFeet(float $bf): string
    {
        if ($bf >= 1000) {
            return number_format($bf / 1000, 1) . 'k BF';
        }

        return number_format($bf, 0) . ' BF';
    }

    protected function getWeekColor(float $weekAvg, float $thirtyDayAvg): string
    {
        if ($thirtyDayAvg <= 0) {
            return 'gray';
        }

        $ratio = $weekAvg / $thirtyDayAvg;

        return match (true) {
            $ratio >= 1.1 => 'success',
            $ratio >= 0.9 => 'info',
            $ratio >= 0.7 => 'warning',
            default => 'danger',
        };
    }
}
