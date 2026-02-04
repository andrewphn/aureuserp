<?php

namespace Webkul\Project\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Project\Services\CncProductionStatsService;

/**
 * CNC Production Stats Widget
 *
 * Displays daily board feet capacity and production metrics
 * based on actual CNC cutting data from completed parts.
 */
class CncProductionStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $service = app(CncProductionStatsService::class);

        // Get stats for last 30 days
        $capacity = $service->getDailyCapacitySummary(
            Carbon::now()->subDays(30),
            Carbon::now()
        );

        $today = $service->getTodayStats();

        return [
            Stat::make('Today\'s Production', $today['sheets_today'] . ' sheets')
                ->description($today['bf_today'] . ' board feet')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($today['sheets_today'] >= $capacity['average_sheets_per_day'] ? 'success' : 'warning')
                ->chart($this->getDailyChart($service)),

            Stat::make('Avg Daily Capacity', $capacity['average_sheets_per_day'] . ' sheets/day')
                ->description('~' . $capacity['average_bf_per_day'] . ' BF/day (30-day avg)')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),

            Stat::make('Peak Day', $capacity['peak_sheets'] . ' sheets')
                ->description('~' . $capacity['peak_bf'] . ' BF (' . ($capacity['peak_date'] ? Carbon::parse($capacity['peak_date'])->format('M j') : 'N/A') . ')')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->color('success'),

            Stat::make('Queue Status', $today['parts_running'] . ' running')
                ->description($today['parts_pending'] . ' parts pending')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color($today['parts_running'] > 0 ? 'info' : 'gray'),
        ];
    }

    /**
     * Get daily sheet counts for the sparkline chart
     */
    protected function getDailyChart(CncProductionStatsService $service): array
    {
        $daily = $service->getDailySheetCounts(
            Carbon::now()->subDays(14),
            Carbon::now()
        );

        // Return array of sheet counts for last 14 days
        return $daily->pluck('sheets')->toArray();
    }

    public static function canView(): bool
    {
        return true;
    }
}
