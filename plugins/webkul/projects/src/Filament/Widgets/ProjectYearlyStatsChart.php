<?php

namespace Webkul\Project\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;

class ProjectYearlyStatsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'projectYearlyStatsChart';

    protected static ?string $heading = 'Linear Feet by Quarter';

    protected static ?int $contentHeight = 120;

    protected int | string | array $columnSpan = 1;

    public ?int $year = null;

    public ?int $quarter = null;

    protected static bool $deferLoading = true;

    public function mount(): void
    {
        $this->year = $this->year ?? now()->year;
        $this->quarter = $this->quarter ?? ceil(now()->month / 3);
    }

    public function placeholder(): \Illuminate\Contracts\View\View
    {
        return view('filament::components.loading-indicator');
    }

    protected function getOptions(): array
    {
        $year = $this->year ?? now()->year;
        $quarter = $this->quarter ?? ceil(now()->month / 3);

        // Get months for this quarter
        $quarterMonths = [
            1 => ['Jan', 'Feb', 'Mar'],
            2 => ['Apr', 'May', 'Jun'],
            3 => ['Jul', 'Aug', 'Sep'],
            4 => ['Oct', 'Nov', 'Dec'],
        ];
        $months = $quarterMonths[$quarter];
        $startMonth = ($quarter - 1) * 3 + 1;

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
        $cancelled = [];

        for ($i = 0; $i < 3; $i++) {
            $month = $startMonth + $i;
            $startOfMonth = now()->setYear($year)->setMonth($month)->startOfMonth();
            $endOfMonth = now()->setYear($year)->setMonth($month)->endOfMonth();

            $completed[] = (int) Project::whereIn('stage_id', $doneStages)
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->sum('estimated_linear_feet');

            $inProgress[] = (int) Project::whereNotIn('stage_id', array_merge($doneStages, $cancelledStages))
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('estimated_linear_feet');

            $cancelled[] = (int) Project::whereIn('stage_id', $cancelledStages)
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->sum('estimated_linear_feet');
        }

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
                    'name' => 'Completed LF',
                    'data' => $completed,
                    'color' => '#22c55e',
                ],
                [
                    'name' => 'In Progress LF',
                    'data' => $inProgress,
                    'color' => '#3b82f6',
                ],
                [
                    'name' => 'Cancelled LF',
                    'data' => $cancelled,
                    'color' => '#6b7280',
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
            \Filament\Forms\Components\Select::make('quarter')
                ->options([
                    1 => 'Q1',
                    2 => 'Q2',
                    3 => 'Q3',
                    4 => 'Q4',
                ])
                ->default(ceil(now()->month / 3))
                ->live()
                ->afterStateUpdated(fn () => $this->updateOptions()),
            \Filament\Forms\Components\Select::make('year')
                ->options(function () {
                    $years = [];
                    $currentYear = now()->year;
                    for ($i = $currentYear; $i >= $currentYear - 2; $i--) {
                        $years[$i] = (string) $i;
                    }
                    return $years;
                })
                ->default(now()->year)
                ->live()
                ->afterStateUpdated(fn () => $this->updateOptions()),
        ];
    }
}
