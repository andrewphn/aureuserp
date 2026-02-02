<?php

namespace Webkul\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Services\Calculators\BoardFootCalculator;

/**
 * CNC Capacity Analytics Service
 *
 * Provides comprehensive analysis of CNC production capacity using
 * actual VCarve data. Calculates board feet production rates, identifies
 * peak capacity, and generates production reports.
 *
 * Key metrics:
 * - Daily/Weekly/Monthly board feet output
 * - Material breakdown by type
 * - Peak vs average capacity
 * - Sheet utilization efficiency
 */
class CncCapacityAnalyticsService
{
    protected BoardFootCalculator $calculator;

    public function __construct(BoardFootCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Calculate board feet cut on a specific date
     *
     * @param Carbon $date The date to calculate for
     * @return array Daily production summary
     */
    public function calculateDailyBoardFeet(Carbon $date): array
    {
        $parts = CncProgramPart::whereDate('completed_at', $date)
            ->where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereNotNull('vcarve_metadata')
            ->with('cncProgram')
            ->get();

        $totalBoardFeet = 0;
        $materialBreakdown = [];
        $sheetCount = 0;

        foreach ($parts as $part) {
            $metadata = $part->vcarve_metadata;
            if (!$metadata || empty($metadata['material'])) {
                continue;
            }

            $material = $metadata['material'];
            $thickness = (float) ($material['thickness'] ?? 0.75);
            $width = (float) ($material['width'] ?? 48);
            $height = (float) ($material['height'] ?? 96);

            $bf = $this->calculator->calculateBoardFeet($thickness, $width, $height);
            $totalBoardFeet += $bf;
            $sheetCount++;

            $materialCode = $part->cncProgram->material_code ?? 'Unknown';
            if (!isset($materialBreakdown[$materialCode])) {
                $materialBreakdown[$materialCode] = [
                    'board_feet' => 0,
                    'sheets' => 0,
                ];
            }
            $materialBreakdown[$materialCode]['board_feet'] += $bf;
            $materialBreakdown[$materialCode]['sheets']++;
        }

        return [
            'date' => $date->toDateString(),
            'total_board_feet' => round($totalBoardFeet, 2),
            'total_sheets' => $sheetCount,
            'average_bf_per_sheet' => $sheetCount > 0 ? round($totalBoardFeet / $sheetCount, 2) : 0,
            'by_material' => $materialBreakdown,
        ];
    }

    /**
     * Get average daily capacity over a date range
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array Capacity analysis
     */
    public function getAverageCapacity(Carbon $start, Carbon $end): array
    {
        $dailyData = [];
        $workingDays = 0;
        $totalBoardFeet = 0;
        $totalSheets = 0;

        $current = $start->copy();
        while ($current <= $end) {
            $dayData = $this->calculateDailyBoardFeet($current);

            // Only count days with production as working days
            if ($dayData['total_sheets'] > 0) {
                $workingDays++;
                $totalBoardFeet += $dayData['total_board_feet'];
                $totalSheets += $dayData['total_sheets'];
                $dailyData[] = $dayData;
            }

            $current->addDay();
        }

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'calendar_days' => $start->diffInDays($end) + 1,
            'working_days' => $workingDays,
            'total_board_feet' => round($totalBoardFeet, 2),
            'total_sheets' => $totalSheets,
            'average_bf_per_day' => $workingDays > 0 ? round($totalBoardFeet / $workingDays, 2) : 0,
            'average_sheets_per_day' => $workingDays > 0 ? round($totalSheets / $workingDays, 1) : 0,
            'daily_breakdown' => $dailyData,
        ];
    }

    /**
     * Get the top production days
     *
     * @param int $limit Number of top days to return
     * @param Carbon|null $since Only consider days after this date
     * @return Collection Peak production days
     */
    public function getPeakDays(int $limit = 10, ?Carbon $since = null): Collection
    {
        $query = CncProgramPart::select(
            DB::raw('DATE(completed_at) as date'),
            DB::raw('COUNT(*) as sheet_count')
        )
            ->where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereNotNull('completed_at')
            ->whereNotNull('vcarve_metadata');

        if ($since) {
            $query->where('completed_at', '>=', $since);
        }

        $topDays = $query
            ->groupBy('date')
            ->orderBy('sheet_count', 'desc')
            ->limit($limit)
            ->get();

        return $topDays->map(function ($day) {
            $date = Carbon::parse($day->date);
            $dayData = $this->calculateDailyBoardFeet($date);

            return [
                'date' => $day->date,
                'day_of_week' => $date->format('l'),
                'sheet_count' => $day->sheet_count,
                'board_feet' => $dayData['total_board_feet'],
                'by_material' => $dayData['by_material'],
            ];
        });
    }

    /**
     * Get board feet breakdown by material type
     *
     * @param Carbon|null $start Start date (optional)
     * @param Carbon|null $end End date (optional)
     * @return array Material breakdown
     */
    public function getMaterialBreakdown(?Carbon $start = null, ?Carbon $end = null): array
    {
        $query = CncProgramPart::query()
            ->where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereNotNull('vcarve_metadata')
            ->with('cncProgram');

        if ($start) {
            $query->where('completed_at', '>=', $start);
        }
        if ($end) {
            $query->where('completed_at', '<=', $end);
        }

        $parts = $query->get();

        $breakdown = [];
        $totalBoardFeet = 0;
        $totalSheets = 0;

        foreach ($parts as $part) {
            $metadata = $part->vcarve_metadata;
            if (!$metadata || empty($metadata['material'])) {
                continue;
            }

            $material = $metadata['material'];
            $thickness = (float) ($material['thickness'] ?? 0.75);
            $width = (float) ($material['width'] ?? 48);
            $height = (float) ($material['height'] ?? 96);

            $bf = $this->calculator->calculateBoardFeet($thickness, $width, $height);
            $totalBoardFeet += $bf;
            $totalSheets++;

            $materialCode = $part->cncProgram->material_code ?? 'Unknown';
            $materialType = $part->cncProgram->material_type ?? $materialCode;

            if (!isset($breakdown[$materialCode])) {
                $breakdown[$materialCode] = [
                    'code' => $materialCode,
                    'type' => $materialType,
                    'board_feet' => 0,
                    'sheets' => 0,
                    'percentage' => 0,
                    'average_bf_per_sheet' => 0,
                ];
            }
            $breakdown[$materialCode]['board_feet'] += $bf;
            $breakdown[$materialCode]['sheets']++;
        }

        // Calculate percentages and averages
        foreach ($breakdown as $code => &$data) {
            $data['percentage'] = $totalBoardFeet > 0
                ? round(($data['board_feet'] / $totalBoardFeet) * 100, 1)
                : 0;
            $data['average_bf_per_sheet'] = $data['sheets'] > 0
                ? round($data['board_feet'] / $data['sheets'], 2)
                : 0;
            $data['board_feet'] = round($data['board_feet'], 2);
        }

        // Sort by board feet descending
        uasort($breakdown, fn($a, $b) => $b['board_feet'] <=> $a['board_feet']);

        return [
            'total_board_feet' => round($totalBoardFeet, 2),
            'total_sheets' => $totalSheets,
            'materials' => array_values($breakdown),
        ];
    }

    /**
     * Generate a comprehensive capacity report
     *
     * @param Carbon|null $start Start date (defaults to 90 days ago)
     * @param Carbon|null $end End date (defaults to today)
     * @return array Complete capacity report
     */
    public function getCapacityReport(?Carbon $start = null, ?Carbon $end = null): array
    {
        $end = $end ?? Carbon::today();
        $start = $start ?? $end->copy()->subDays(90);

        $capacityData = $this->getAverageCapacity($start, $end);
        $materialBreakdown = $this->getMaterialBreakdown($start, $end);
        $peakDays = $this->getPeakDays(10, $start);

        // Calculate weekly averages
        $weeklyAverages = $this->getWeeklyAverages($start, $end);

        // Calculate monthly totals
        $monthlyTotals = $this->getMonthlyTotals($start, $end);

        // Get estimated vs actual comparison if available
        $estimateComparison = $this->getEstimateAccuracy($start, $end);

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => [
                'total_board_feet' => $capacityData['total_board_feet'],
                'total_sheets' => $capacityData['total_sheets'],
                'working_days' => $capacityData['working_days'],
                'average_bf_per_day' => $capacityData['average_bf_per_day'],
                'average_sheets_per_day' => $capacityData['average_sheets_per_day'],
            ],
            'peak_day' => $peakDays->first(),
            'peak_days' => $peakDays->toArray(),
            'material_breakdown' => $materialBreakdown,
            'weekly_averages' => $weeklyAverages,
            'monthly_totals' => $monthlyTotals,
            'estimate_accuracy' => $estimateComparison,
            'daily_data' => $capacityData['daily_breakdown'],
        ];
    }

    /**
     * Get weekly averages for trending
     */
    protected function getWeeklyAverages(Carbon $start, Carbon $end): array
    {
        $weeks = [];
        $current = $start->copy()->startOfWeek();

        while ($current <= $end) {
            $weekEnd = $current->copy()->endOfWeek();
            if ($weekEnd > $end) {
                $weekEnd = $end->copy();
            }

            $weekData = $this->getAverageCapacity($current, $weekEnd);

            if ($weekData['working_days'] > 0) {
                $weeks[] = [
                    'week_start' => $current->toDateString(),
                    'week_end' => $weekEnd->toDateString(),
                    'working_days' => $weekData['working_days'],
                    'total_board_feet' => $weekData['total_board_feet'],
                    'total_sheets' => $weekData['total_sheets'],
                    'avg_bf_per_day' => $weekData['average_bf_per_day'],
                ];
            }

            $current->addWeek();
        }

        return $weeks;
    }

    /**
     * Get monthly totals for trending
     */
    protected function getMonthlyTotals(Carbon $start, Carbon $end): array
    {
        $months = [];
        $current = $start->copy()->startOfMonth();

        while ($current <= $end) {
            $monthEnd = $current->copy()->endOfMonth();
            if ($monthEnd > $end) {
                $monthEnd = $end->copy();
            }

            $monthStart = $current->copy();
            if ($monthStart < $start) {
                $monthStart = $start->copy();
            }

            $monthData = $this->getAverageCapacity($monthStart, $monthEnd);

            if ($monthData['working_days'] > 0) {
                $months[] = [
                    'month' => $current->format('Y-m'),
                    'month_name' => $current->format('F Y'),
                    'working_days' => $monthData['working_days'],
                    'total_board_feet' => $monthData['total_board_feet'],
                    'total_sheets' => $monthData['total_sheets'],
                    'avg_bf_per_day' => $monthData['average_bf_per_day'],
                ];
            }

            $current->addMonth();
        }

        return $months;
    }

    /**
     * Compare estimated vs actual sheets used
     */
    protected function getEstimateAccuracy(Carbon $start, Carbon $end): array
    {
        $programs = CncProgram::where('status', CncProgram::STATUS_COMPLETE)
            ->whereNotNull('sheets_estimated')
            ->whereNotNull('sheets_actual')
            ->where('nested_at', '>=', $start)
            ->where('nested_at', '<=', $end)
            ->get();

        if ($programs->isEmpty()) {
            return [
                'has_data' => false,
                'message' => 'No programs with estimate comparisons in this period',
            ];
        }

        $totalEstimated = $programs->sum('sheets_estimated');
        $totalActual = $programs->sum('sheets_actual');
        $totalVariance = $totalActual - $totalEstimated;

        $accurateCount = $programs->filter(fn($p) => abs($p->sheets_variance) <= 1)->count();

        return [
            'has_data' => true,
            'programs_compared' => $programs->count(),
            'total_estimated_sheets' => $totalEstimated,
            'total_actual_sheets' => $totalActual,
            'total_variance' => $totalVariance,
            'variance_percentage' => $totalEstimated > 0
                ? round(($totalVariance / $totalEstimated) * 100, 1)
                : 0,
            'accuracy_rate' => $programs->count() > 0
                ? round(($accurateCount / $programs->count()) * 100, 1)
                : 0,
            'average_utilization' => round($programs->avg('utilization_percentage'), 1),
        ];
    }

    /**
     * Quick stats for dashboard widget
     */
    public function getDashboardStats(): array
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();
        $monthStart = $today->copy()->startOfMonth();

        $todayData = $this->calculateDailyBoardFeet($today);
        $weekData = $this->getAverageCapacity($weekStart, $today);
        $monthData = $this->getAverageCapacity($monthStart, $today);

        // Get 30-day trend
        $thirtyDaysAgo = $today->copy()->subDays(30);
        $thirtyDayData = $this->getAverageCapacity($thirtyDaysAgo, $today);

        return [
            'today' => [
                'board_feet' => $todayData['total_board_feet'],
                'sheets' => $todayData['total_sheets'],
            ],
            'this_week' => [
                'board_feet' => $weekData['total_board_feet'],
                'sheets' => $weekData['total_sheets'],
                'avg_per_day' => $weekData['average_bf_per_day'],
                'working_days' => $weekData['working_days'],
            ],
            'this_month' => [
                'board_feet' => $monthData['total_board_feet'],
                'sheets' => $monthData['total_sheets'],
                'avg_per_day' => $monthData['average_bf_per_day'],
                'working_days' => $monthData['working_days'],
            ],
            'thirty_day_avg' => [
                'bf_per_day' => $thirtyDayData['average_bf_per_day'],
                'sheets_per_day' => $thirtyDayData['average_sheets_per_day'],
                'working_days' => $thirtyDayData['working_days'],
            ],
        ];
    }
}
