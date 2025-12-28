<?php

namespace Webkul\Project\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;

class KanbanKpiService
{
    /**
     * Get date range based on time range setting
     */
    public function getDateRange(string $timeRange): array
    {
        return match ($timeRange) {
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'ytd' => [now()->startOfYear(), now()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };
    }

    /**
     * Get time range label
     */
    public function getTimeRangeLabel(string $timeRange): string
    {
        return match ($timeRange) {
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'this_quarter' => 'This Quarter',
            'ytd' => 'Year to Date',
            default => 'This Week',
        };
    }

    /**
     * Get business owner KPI stats
     */
    public function getKpiStats(string $timeRange): array
    {
        [$startDate, $endDate] = $this->getDateRange($timeRange);

        $productionStages = $this->getProductionStageIds();
        $doneStages = $this->getDoneStageIds();

        // Linear feet created this period
        $lfThisPeriod = Project::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('estimated_linear_feet') ?? 0;

        // Linear feet currently in production
        $lfInProduction = Project::query()
            ->whereIn('stage_id', $productionStages)
            ->sum('estimated_linear_feet') ?? 0;

        // Projects in production count
        $projectsInProduction = Project::query()
            ->whereIn('stage_id', $productionStages)
            ->count();

        // On target: Projects with completion date >= today (not overdue)
        $onTargetCount = Project::query()
            ->whereNotIn('stage_id', $doneStages)
            ->where(function ($q) {
                $q->whereNull('desired_completion_date')
                    ->orWhere('desired_completion_date', '>=', now());
            })
            ->whereDoesntHave('tasks', fn($t) => $t->where('state', 'blocked'))
            ->count();

        // Off target: Overdue or blocked
        $offTargetCount = Project::query()
            ->whereNotIn('stage_id', $doneStages)
            ->where(function ($q) {
                $q->where('desired_completion_date', '<', now())
                    ->orWhereHas('tasks', fn($t) => $t->where('state', 'blocked'));
            })
            ->count();

        // Completed this period
        $completedThisPeriod = Project::query()
            ->whereIn('stage_id', $doneStages)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // LF completed this period
        $lfCompletedThisPeriod = Project::query()
            ->whereIn('stage_id', $doneStages)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('estimated_linear_feet') ?? 0;

        return [
            'lf_this_period' => round($lfThisPeriod, 1),
            'lf_in_production' => round($lfInProduction, 1),
            'projects_in_production' => $projectsInProduction,
            'on_target' => $onTargetCount,
            'off_target' => $offTargetCount,
            'completed_this_period' => $completedThisPeriod,
            'lf_completed_this_period' => round($lfCompletedThisPeriod, 1),
            'time_range_label' => $this->getTimeRangeLabel($timeRange),
        ];
    }

    /**
     * Get yearly statistics for chart
     */
    public function getYearlyStats(int $year): array
    {
        $doneStages = $this->getDoneStageIds();
        $cancelledStages = $this->getCancelledStageIds();

        // Initialize monthly data
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = [
                'month' => date('M', mktime(0, 0, 0, $m, 1)),
                'completed' => 0,
                'in_progress' => 0,
                'cancelled' => 0,
            ];
        }

        // Query completed projects
        $completed = Project::query()
            ->whereIn('stage_id', $doneStages)
            ->whereYear('updated_at', $year)
            ->selectRaw('MONTH(updated_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Query cancelled projects
        $cancelled = Project::query()
            ->whereIn('stage_id', $cancelledStages)
            ->whereYear('updated_at', $year)
            ->selectRaw('MONTH(updated_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Query in-progress projects
        $inProgress = Project::query()
            ->whereNotIn('stage_id', array_merge($doneStages, $cancelledStages))
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Merge into monthly data
        foreach ($months as $m => &$data) {
            $data['completed'] = $completed[$m] ?? 0;
            $data['cancelled'] = $cancelled[$m] ?? 0;
            $data['in_progress'] = $inProgress[$m] ?? 0;
        }

        return [
            'labels' => array_column($months, 'month'),
            'datasets' => [
                [
                    'label' => 'Completed',
                    'data' => array_column($months, 'completed'),
                    'backgroundColor' => '#16a34a',
                ],
                [
                    'label' => 'In Progress',
                    'data' => array_column($months, 'in_progress'),
                    'backgroundColor' => '#2563eb',
                ],
                [
                    'label' => 'Cancelled',
                    'data' => array_column($months, 'cancelled'),
                    'backgroundColor' => '#6b7280',
                ],
            ],
            'year' => $year,
            'totals' => [
                'completed' => array_sum(array_column($months, 'completed')),
                'in_progress' => array_sum(array_column($months, 'in_progress')),
                'cancelled' => array_sum(array_column($months, 'cancelled')),
            ],
        ];
    }

    /**
     * Get available years for chart dropdown
     */
    public function getAvailableYears(): array
    {
        $currentYear = now()->year;
        $years = [];

        $earliestProject = Project::query()->orderBy('created_at')->first();
        $startYear = $earliestProject ? $earliestProject->created_at->year : $currentYear;

        for ($year = $currentYear; $year >= $startYear; $year--) {
            $years[$year] = (string) $year;
        }

        return $years;
    }

    /**
     * Get production stage IDs
     */
    protected function getProductionStageIds(): array
    {
        return ProjectStage::query()
            ->whereIn(DB::raw('LOWER(name)'), [
                'in production', 'production', 'manufacturing', 'fabrication',
                'assembly', 'finishing', 'install', 'installation'
            ])
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get done stage IDs
     */
    protected function getDoneStageIds(): array
    {
        return ProjectStage::query()
            ->whereIn(DB::raw('LOWER(name)'), ['done', 'completed', 'finished'])
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get cancelled stage IDs
     */
    protected function getCancelledStageIds(): array
    {
        return ProjectStage::query()
            ->whereIn(DB::raw('LOWER(name)'), ['cancelled', 'canceled'])
            ->pluck('id')
            ->toArray();
    }
}
