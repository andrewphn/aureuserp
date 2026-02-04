<?php

namespace Webkul\Project\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Webkul\Project\Models\ProductionMetricsDaily;

/**
 * Production Metrics Trend Widget
 *
 * Displays a 30-day trend chart of production metrics from the
 * persisted projects_production_metrics_daily table.
 *
 * Unlike CncCapacityChartWidget, this reads from pre-aggregated data
 * for better performance.
 */
class ProductionMetricsTrendWidget extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Production Metrics Trend';

    protected ?string $description = '30-day production from aggregated metrics';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(30);

        // Get aggregated metrics from the database
        $metrics = ProductionMetricsDaily::dateRange($start, $end)
            ->orderBy('metrics_date')
            ->get()
            ->keyBy(fn($m) => $m->metrics_date->toDateString());

        // Fill in missing days
        $dates = [];
        $boardFeet = [];
        $sheets = [];
        $utilization = [];

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->toDateString();
            $dates[] = $current->format('M j');

            $dayMetrics = $metrics->get($dateStr);

            $boardFeet[] = $dayMetrics ? (float) $dayMetrics->board_feet : 0;
            $sheets[] = $dayMetrics ? $dayMetrics->sheets_completed : 0;
            $utilization[] = $dayMetrics && $dayMetrics->utilization_avg
                ? (float) $dayMetrics->utilization_avg
                : null;

            $current->addDay();
        }

        // Calculate statistics for subtitle
        $workingDays = $metrics->filter(fn($m) => $m->sheets_completed > 0)->count();
        $totalBf = $metrics->sum('board_feet');
        $avgBfPerDay = $workingDays > 0 ? round($totalBf / $workingDays) : 0;

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
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
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
                    'beginAtZero' => true,
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Sheets',
                    ],
                    'beginAtZero' => true,
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    /**
     * Get summary statistics for the header
     */
    public static function getStats(): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(30);

        $metrics = ProductionMetricsDaily::dateRange($start, $end)
            ->withProduction()
            ->get();

        if ($metrics->isEmpty()) {
            return [
                'avg_bf_per_day' => 0,
                'avg_sheets_per_day' => 0,
                'working_days' => 0,
                'total_bf' => 0,
            ];
        }

        $workingDays = $metrics->count();

        return [
            'avg_bf_per_day' => round($metrics->sum('board_feet') / $workingDays, 1),
            'avg_sheets_per_day' => round($metrics->sum('sheets_completed') / $workingDays, 1),
            'working_days' => $workingDays,
            'total_bf' => round($metrics->sum('board_feet'), 0),
        ];
    }
}
