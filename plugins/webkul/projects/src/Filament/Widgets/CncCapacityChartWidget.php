<?php

namespace Webkul\Project\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Webkul\Project\Services\CncCapacityAnalyticsService;

/**
 * CNC Capacity Chart Widget
 *
 * Displays a trend chart of daily board feet production over the past 30 days.
 */
class CncCapacityChartWidget extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Daily Board Feet Production';

    protected ?string $description = 'Last 30 days of CNC production';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $service = app(CncCapacityAnalyticsService::class);
        $end = Carbon::today();
        $start = $end->copy()->subDays(30);

        $report = $service->getCapacityReport($start, $end);
        $dailyData = $report['daily_data'];

        // Fill in missing days with zeros
        $dates = [];
        $boardFeet = [];
        $sheets = [];

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->toDateString();
            $dates[] = $current->format('M j');

            $dayData = collect($dailyData)->firstWhere('date', $dateStr);
            $boardFeet[] = $dayData ? $dayData['total_board_feet'] : 0;
            $sheets[] = $dayData ? $dayData['total_sheets'] : 0;

            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Board Feet',
                    'data' => $boardFeet,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Sheets',
                    'data' => $sheets,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [5, 5],
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Board Feet',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Sheets',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
