<?php

namespace Webkul\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;

/**
 * CNC Production Statistics Service
 *
 * Calculates production metrics from actual CNC cutting data:
 * - Daily/weekly/monthly sheets completed
 * - Board feet capacity
 * - Operator productivity
 * - Utilization trends
 */
class CncProductionStatsService
{
    /**
     * Board feet per 4x8 sheet of 3/4" material
     * Formula: (48 * 96 / 144) * (0.75 / 1) = 24 BF per sheet
     * Using 20 BF as a practical estimate (accounting for waste)
     */
    protected const BF_PER_SHEET = 20;

    /**
     * Get daily capacity summary for a date range
     */
    public function getDailyCapacitySummary(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $dailyStats = $this->getDailySheetCounts($startDate, $endDate);

        if ($dailyStats->isEmpty()) {
            return [
                'average_sheets_per_day' => 0,
                'average_bf_per_day' => 0,
                'peak_sheets' => 0,
                'peak_bf' => 0,
                'peak_date' => null,
                'slow_sheets' => 0,
                'slow_bf' => 0,
                'slow_date' => null,
                'total_sheets' => 0,
                'total_bf' => 0,
                'working_days' => 0,
                'daily_breakdown' => [],
            ];
        }

        $totalSheets = $dailyStats->sum('sheets');
        $workingDays = $dailyStats->count();
        $avgSheets = round($totalSheets / $workingDays, 1);

        $peakDay = $dailyStats->sortByDesc('sheets')->first();
        $slowDay = $dailyStats->sortBy('sheets')->first();

        return [
            'average_sheets_per_day' => $avgSheets,
            'average_bf_per_day' => round($avgSheets * self::BF_PER_SHEET),
            'peak_sheets' => $peakDay['sheets'],
            'peak_bf' => $peakDay['sheets'] * self::BF_PER_SHEET,
            'peak_date' => $peakDay['date'],
            'slow_sheets' => $slowDay['sheets'],
            'slow_bf' => $slowDay['sheets'] * self::BF_PER_SHEET,
            'slow_date' => $slowDay['date'],
            'total_sheets' => $totalSheets,
            'total_bf' => $totalSheets * self::BF_PER_SHEET,
            'working_days' => $workingDays,
            'daily_breakdown' => $dailyStats->toArray(),
        ];
    }

    /**
     * Get sheets completed per day
     */
    public function getDailySheetCounts(Carbon $startDate, Carbon $endDate): Collection
    {
        // Count unique sheets completed per day
        // A "sheet" is defined by cnc_program_id + sheet_number
        $results = CncProgramPart::query()
            ->select([
                DB::raw('DATE(completed_at) as completion_date'),
                DB::raw('COUNT(DISTINCT CONCAT(cnc_program_id, "-", COALESCE(sheet_number, id))) as unique_sheets'),
                DB::raw('COUNT(*) as total_parts'),
            ])
            ->where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->orderBy('completion_date')
            ->get();

        return $results->map(function ($row) {
            return [
                'date' => $row->completion_date,
                'sheets' => (int) $row->unique_sheets,
                'parts' => (int) $row->total_parts,
                'bf' => (int) $row->unique_sheets * self::BF_PER_SHEET,
            ];
        });
    }

    /**
     * Get weekly summary
     */
    public function getWeeklySummary(int $weeks = 4): Collection
    {
        $results = CncProgramPart::query()
            ->select([
                DB::raw('YEARWEEK(completed_at, 1) as year_week'),
                DB::raw('MIN(DATE(completed_at)) as week_start'),
                DB::raw('COUNT(DISTINCT CONCAT(cnc_program_id, "-", COALESCE(sheet_number, id))) as sheets'),
                DB::raw('COUNT(*) as parts'),
            ])
            ->where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', Carbon::now()->subWeeks($weeks))
            ->groupBy(DB::raw('YEARWEEK(completed_at, 1)'))
            ->orderBy('year_week', 'desc')
            ->get();

        return $results->map(function ($row) {
            return [
                'week' => $row->year_week,
                'week_start' => $row->week_start,
                'sheets' => (int) $row->sheets,
                'parts' => (int) $row->parts,
                'bf' => (int) $row->sheets * self::BF_PER_SHEET,
            ];
        });
    }

    /**
     * Get operator productivity stats
     */
    public function getOperatorStats(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        return CncProgramPart::query()
            ->select([
                'operator_id',
                DB::raw('COUNT(*) as parts_completed'),
                DB::raw('COUNT(DISTINCT CONCAT(cnc_program_id, "-", COALESCE(sheet_number, id))) as sheets_completed'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, run_at, completed_at)) as avg_minutes_per_part'),
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, run_at, completed_at)) as total_minutes'),
            ])
            ->with('operator:id,name')
            ->where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereNotNull('completed_at')
            ->whereNotNull('operator_id')
            ->whereBetween('completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('operator_id')
            ->orderByDesc('sheets_completed')
            ->get()
            ->map(function ($row) {
                return [
                    'operator_id' => $row->operator_id,
                    'operator_name' => $row->operator?->name ?? 'Unknown',
                    'parts_completed' => (int) $row->parts_completed,
                    'sheets_completed' => (int) $row->sheets_completed,
                    'bf_completed' => (int) $row->sheets_completed * self::BF_PER_SHEET,
                    'avg_minutes_per_part' => round($row->avg_minutes_per_part ?? 0, 1),
                    'total_hours' => round(($row->total_minutes ?? 0) / 60, 1),
                ];
            });
    }

    /**
     * Get material breakdown stats
     */
    public function getMaterialStats(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        return CncProgramPart::query()
            ->join('projects_cnc_programs', 'projects_cnc_program_parts.cnc_program_id', '=', 'projects_cnc_programs.id')
            ->select([
                'projects_cnc_programs.material_code',
                DB::raw('COUNT(DISTINCT CONCAT(projects_cnc_program_parts.cnc_program_id, "-", COALESCE(projects_cnc_program_parts.sheet_number, projects_cnc_program_parts.id))) as sheets'),
                DB::raw('COUNT(*) as parts'),
            ])
            ->where('projects_cnc_program_parts.status', CncProgramPart::STATUS_COMPLETE)
            ->whereNotNull('projects_cnc_program_parts.completed_at')
            ->whereBetween('projects_cnc_program_parts.completed_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('projects_cnc_programs.material_code')
            ->orderByDesc('sheets')
            ->get()
            ->map(function ($row) {
                return [
                    'material_code' => $row->material_code ?? 'Unknown',
                    'material_name' => CncProgram::getMaterialCodes()[$row->material_code] ?? $row->material_code,
                    'sheets' => (int) $row->sheets,
                    'parts' => (int) $row->parts,
                    'bf' => (int) $row->sheets * self::BF_PER_SHEET,
                ];
            });
    }

    /**
     * Get today's production stats
     */
    public function getTodayStats(): array
    {
        $today = Carbon::today();

        $stats = CncProgramPart::query()
            ->select([
                DB::raw('COUNT(DISTINCT CONCAT(cnc_program_id, "-", COALESCE(sheet_number, id))) as sheets'),
                DB::raw('COUNT(*) as parts'),
            ])
            ->where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereDate('completed_at', $today)
            ->first();

        $running = CncProgramPart::query()
            ->where('status', CncProgramPart::STATUS_RUNNING)
            ->count();

        $pending = CncProgramPart::query()
            ->where('status', CncProgramPart::STATUS_PENDING)
            ->whereHas('cncProgram', function ($q) {
                $q->where('status', '!=', CncProgram::STATUS_COMPLETE);
            })
            ->count();

        return [
            'sheets_today' => (int) ($stats->sheets ?? 0),
            'bf_today' => (int) ($stats->sheets ?? 0) * self::BF_PER_SHEET,
            'parts_today' => (int) ($stats->parts ?? 0),
            'parts_running' => $running,
            'parts_pending' => $pending,
        ];
    }

    /**
     * Get utilization summary from completed programs
     */
    public function getUtilizationSummary(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $programs = CncProgram::query()
            ->whereNotNull('utilization_percentage')
            ->whereBetween('nested_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        if ($programs->isEmpty()) {
            return [
                'average_utilization' => null,
                'best_utilization' => null,
                'worst_utilization' => null,
                'total_programs' => 0,
                'total_waste_sqft' => 0,
            ];
        }

        return [
            'average_utilization' => round($programs->avg('utilization_percentage'), 1),
            'best_utilization' => round($programs->max('utilization_percentage'), 1),
            'worst_utilization' => round($programs->min('utilization_percentage'), 1),
            'total_programs' => $programs->count(),
            'total_waste_sqft' => round($programs->sum('waste_sqft'), 1),
        ];
    }

    /**
     * Generate a formatted capacity report (like the one shown to user)
     */
    public function generateCapacityReport(?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        $stats = $this->getDailyCapacitySummary($startDate, $endDate);

        if ($stats['working_days'] === 0) {
            return "No CNC production data available for the selected period.";
        }

        $report = "CNC Daily Board Feet Capacity Summary\n";
        $report .= str_repeat('=', 50) . "\n\n";

        $report .= sprintf("%-20s %10s %15s\n", 'Metric', 'Sheets', 'Board Feet');
        $report .= str_repeat('-', 50) . "\n";

        $report .= sprintf(
            "%-20s %10s %15s\n",
            'Average',
            $stats['average_sheets_per_day'] . '/day',
            '~' . $stats['average_bf_per_day'] . ' BF/day'
        );

        $report .= sprintf(
            "%-20s %10s %15s\n",
            'Peak day',
            $stats['peak_sheets'] . '/day',
            '~' . $stats['peak_bf'] . ' BF/day'
        );

        $report .= sprintf(
            "%-20s %10s %15s\n",
            'Slow day',
            $stats['slow_sheets'] . '/day',
            '~' . $stats['slow_bf'] . ' BF/day'
        );

        $report .= str_repeat('-', 50) . "\n";
        $report .= sprintf(
            "%-20s %10s %15s\n",
            'Period Total',
            $stats['total_sheets'] . ' sheets',
            $stats['total_bf'] . ' BF'
        );
        $report .= sprintf("Working days: %d\n", $stats['working_days']);

        if ($stats['peak_date']) {
            $report .= sprintf("\nPeak: %s | Slow: %s\n", $stats['peak_date'], $stats['slow_date']);
        }

        return $report;
    }
}
