<?php

namespace Webkul\Project\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Webkul\Project\Services\CncCapacityAnalyticsService;

/**
 * CNC Material Breakdown Widget
 *
 * Displays a pie/doughnut chart showing board feet distribution by material type.
 */
class CncMaterialBreakdownWidget extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Material Distribution';

    protected ?string $description = 'Board feet by material type (Last 90 days)';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $service = app(CncCapacityAnalyticsService::class);
        $end = Carbon::today();
        $start = $end->copy()->subDays(90);

        $breakdown = $service->getMaterialBreakdown($start, $end);

        $labels = [];
        $data = [];
        $colors = [];

        $colorPalette = [
            '#3b82f6', // Blue
            '#10b981', // Green
            '#f59e0b', // Amber
            '#ef4444', // Red
            '#8b5cf6', // Purple
            '#06b6d4', // Cyan
            '#f97316', // Orange
            '#ec4899', // Pink
        ];

        $colorIndex = 0;
        foreach ($breakdown['materials'] as $material) {
            $labels[] = $material['code'] . ' (' . $material['percentage'] . '%)';
            $data[] = $material['board_feet'];
            $colors[] = $colorPalette[$colorIndex % count($colorPalette)];
            $colorIndex++;
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
