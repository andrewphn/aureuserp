<?php

namespace Webkul\Project\Filament\Resources\CabinetReportResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\Cabinet;

/**
 * Size Distribution Widget Filament widget
 *
 * @see \Filament\Resources\Resource
 */
class SizeDistributionWidget extends ChartWidget
{
    protected ?string $heading = 'Cabinet Size Distribution';

    protected ?string $description = 'Breakdown by size range (linear feet)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $distribution = DB::table('projects_cabinets')
            ->selectRaw("
                CASE
                    WHEN linear_feet <= 1.5 THEN 'Small (12-18\")'
                    WHEN linear_feet <= 3.0 THEN 'Medium (18-36\")'
                    WHEN linear_feet <= 4.0 THEN 'Large (36-48\")'
                    ELSE 'Extra Large (48\"+)'
                END as size_range,
                SUM(quantity) as count
            ")
            ->whereNull('deleted_at')
            ->groupBy('size_range')
            ->orderByRaw("
                CASE size_range
                    WHEN 'Small (12-18\")' THEN 1
                    WHEN 'Medium (18-36\")' THEN 2
                    WHEN 'Large (36-48\")' THEN 3
                    ELSE 4
                END
            ")
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Cabinets Built',
                    'data' => $distribution->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',   // Green - Small
                        'rgb(59, 130, 246)',  // Blue - Medium
                        'rgb(251, 191, 36)',  // Amber - Large
                        'rgb(239, 68, 68)',   // Red - Extra Large
                    ],
                ],
            ],
            'labels' => $distribution->pluck('size_range')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
