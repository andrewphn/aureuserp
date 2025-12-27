<?php

namespace Webkul\Project\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;

class ProjectYearlyStatsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'projectYearlyStatsChart';

    protected static ?string $heading = null;

    protected static ?int $contentHeight = 120;

    protected int | string | array $columnSpan = 'full';

    public ?int $year = null;

    protected static bool $deferLoading = true;

    public function mount(): void
    {
        $this->year = $this->year ?? now()->year;
    }

    public function placeholder(): \Illuminate\Contracts\View\View
    {
        return view('filament::components.loading-indicator');
    }

    protected function getOptions(): array
    {
        $year = $this->year ?? now()->year;
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

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

        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = now()->setYear($year)->setMonth($month)->startOfMonth();
            $endOfMonth = now()->setYear($year)->setMonth($month)->endOfMonth();

            $completed[] = Project::whereIn('stage_id', $doneStages)
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->count();

            $inProgress[] = Project::whereNotIn('stage_id', array_merge($doneStages, $cancelledStages))
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $cancelled[] = Project::whereIn('stage_id', $cancelledStages)
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->count();
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
                    'name' => 'Completed',
                    'data' => $completed,
                    'color' => '#22c55e',
                ],
                [
                    'name' => 'In Progress',
                    'data' => $inProgress,
                    'color' => '#3b82f6',
                ],
                [
                    'name' => 'Cancelled',
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
            \Filament\Forms\Components\Select::make('year')
                ->options(function () {
                    $years = [];
                    $currentYear = now()->year;
                    for ($i = $currentYear; $i >= $currentYear - 3; $i--) {
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
