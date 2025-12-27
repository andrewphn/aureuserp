<?php

namespace Webkul\Project\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;

class ProjectYearlyStatsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'projectYearlyStatsChart';

    protected static ?string $heading = 'Linear Feet';

    protected static ?int $contentHeight = 120;

    protected int | string | array $columnSpan = 1;

    public ?string $timeRange = 'this_month';

    protected static bool $deferLoading = true;

    public function mount(): void
    {
        $this->timeRange = $this->timeRange ?? 'this_month';
    }

    public function placeholder(): \Illuminate\Contracts\View\View
    {
        return view('filament::components.loading-indicator');
    }

    protected function getTimeRangeData(): array
    {
        $timeRange = $this->timeRange ?? 'this_month';

        return match ($timeRange) {
            'this_week' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'periods' => collect(range(0, 6))->map(fn ($d) => [
                    'start' => now()->startOfWeek()->addDays($d)->startOfDay(),
                    'end' => now()->startOfWeek()->addDays($d)->endOfDay(),
                ])->toArray(),
            ],
            'this_month' => [
                'labels' => ['Wk1', 'Wk2', 'Wk3', 'Wk4'],
                'periods' => collect(range(0, 3))->map(fn ($w) => [
                    'start' => now()->startOfMonth()->addWeeks($w)->startOfDay(),
                    'end' => now()->startOfMonth()->addWeeks($w)->endOfWeek()->endOfDay(),
                ])->toArray(),
            ],
            'this_quarter' => [
                'labels' => [
                    now()->startOfQuarter()->format('M'),
                    now()->startOfQuarter()->addMonth()->format('M'),
                    now()->startOfQuarter()->addMonths(2)->format('M'),
                ],
                'periods' => collect(range(0, 2))->map(fn ($m) => [
                    'start' => now()->startOfQuarter()->addMonths($m)->startOfMonth(),
                    'end' => now()->startOfQuarter()->addMonths($m)->endOfMonth(),
                ])->toArray(),
            ],
            'ytd' => [
                'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
                'periods' => collect(range(0, 3))->map(fn ($q) => [
                    'start' => now()->startOfYear()->addQuarters($q)->startOfDay(),
                    'end' => now()->startOfYear()->addQuarters($q)->endOfQuarter()->endOfDay(),
                ])->toArray(),
            ],
            default => [
                'labels' => ['Wk1', 'Wk2', 'Wk3', 'Wk4'],
                'periods' => [],
            ],
        };
    }

    protected function getOptions(): array
    {
        $rangeData = $this->getTimeRangeData();
        $labels = $rangeData['labels'];
        $periods = $rangeData['periods'];

        // Get stage IDs for "completed" and "cancelled" stages
        $doneStages = ProjectStage::query()
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(name)'), ['done', 'completed', 'finished'])
            ->pluck('id')
            ->toArray();

        $cancelledStages = ProjectStage::query()
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(name)'), ['cancelled', 'canceled'])
            ->pluck('id')
            ->toArray();

        $completed = [];
        $inProgress = [];

        foreach ($periods as $period) {
            $completed[] = (int) Project::whereIn('stage_id', $doneStages)
                ->whereBetween('updated_at', [$period['start'], $period['end']])
                ->sum('estimated_linear_feet');

            $inProgress[] = (int) Project::whereNotIn('stage_id', array_merge($doneStages, $cancelledStages))
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->sum('estimated_linear_feet');
        }

        $months = $labels;

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 100,
                'stacked' => true,
                'toolbar' => [
                    'show' => false,
                ],
                'sparkline' => [
                    'enabled' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Completed',
                    'data' => $completed,
                    'color' => '#22c55e',
                ],
                [
                    'name' => 'In Progress',
                    'data' => $inProgress,
                    'color' => '#3b82f6',
                ],
            ],
            'xaxis' => [
                'categories' => $months,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontSize' => '10px',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontSize' => '10px',
                    ],
                ],
            ],
            'legend' => [
                'show' => true,
                'position' => 'top',
                'horizontalAlign' => 'right',
                'fontSize' => '10px',
                'fontFamily' => 'inherit',
                'markers' => [
                    'size' => 8,
                ],
                'itemMargin' => [
                    'horizontal' => 8,
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'columnWidth' => '60%',
                    'borderRadius' => 2,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'grid' => [
                'show' => true,
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 4,
            ],
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Select::make('timeRange')
                ->options([
                    'this_week' => 'Week',
                    'this_month' => 'Month',
                    'this_quarter' => 'Quarter',
                    'ytd' => 'YTD',
                ])
                ->default('this_month')
                ->live()
                ->afterStateUpdated(fn () => $this->updateOptions()),
        ];
    }
}
