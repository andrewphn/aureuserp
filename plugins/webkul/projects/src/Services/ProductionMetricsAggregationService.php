<?php

namespace Webkul\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Models\ProductionMetricsDaily;
use Webkul\Project\Services\Calculators\BoardFootCalculator;

/**
 * Production Metrics Aggregation Service
 *
 * Aggregates CNC production data into the projects_production_metrics_daily table.
 * Uses existing CncProductionStatsService and CncCapacityAnalyticsService internally.
 */
class ProductionMetricsAggregationService
{
    protected const BF_PER_SHEET = 20;

    protected BoardFootCalculator $calculator;

    public function __construct(BoardFootCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Aggregate metrics for a specific date
     */
    public function aggregateForDate(Carbon $date, ?int $companyId = null): ProductionMetricsDaily
    {
        Log::info('Aggregating production metrics', [
            'date' => $date->toDateString(),
            'company_id' => $companyId,
        ]);

        $metrics = ProductionMetricsDaily::findOrCreateForDate($date, $companyId);

        // Get completed parts for this date
        $partsQuery = CncProgramPart::query()
            ->where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereDate('completed_at', $date);

        $parts = $partsQuery->with('cncProgram', 'operator')->get();

        if ($parts->isEmpty()) {
            $metrics->update([
                'sheets_completed' => 0,
                'parts_completed' => 0,
                'board_feet' => 0,
                'sqft_processed' => 0,
                'utilization_avg' => null,
                'waste_sqft' => 0,
                'operator_breakdown' => null,
                'material_breakdown' => null,
                'total_run_minutes' => 0,
                'avg_minutes_per_sheet' => null,
                'programs_completed' => 0,
                'sheets_per_hour' => null,
                'bf_per_hour' => null,
                'computed_at' => now(),
                'is_complete' => $date->lt(Carbon::today()),
            ]);

            return $metrics;
        }

        // Calculate unique sheets (by cnc_program_id + sheet_number)
        $uniqueSheets = $parts->unique(function ($part) {
            return $part->cnc_program_id . '-' . ($part->sheet_number ?? $part->id);
        })->count();

        // Calculate board feet from VCarve metadata or default
        $totalBoardFeet = 0;
        $totalSqft = 0;
        $operatorStats = [];
        $materialStats = [];
        $totalRunMinutes = 0;

        foreach ($parts as $part) {
            // Calculate board feet
            $bf = $this->calculatePartBoardFeet($part);
            $totalBoardFeet += $bf;

            // Calculate sqft (48x96 = 4608 sq inches = 32 sqft per sheet)
            $sqft = $this->calculatePartSqft($part);
            $totalSqft += $sqft;

            // Track run time
            if ($part->run_at && $part->completed_at) {
                $totalRunMinutes += $part->run_at->diffInMinutes($part->completed_at);
            }

            // Operator breakdown
            $operatorId = $part->operator_id ?? 0;
            $operatorName = $part->operator?->name ?? 'Unknown';
            if (!isset($operatorStats[$operatorId])) {
                $operatorStats[$operatorId] = [
                    'operator_id' => $operatorId,
                    'operator_name' => $operatorName,
                    'parts_completed' => 0,
                    'sheets_completed' => 0,
                    'board_feet' => 0,
                    'run_minutes' => 0,
                ];
            }
            $operatorStats[$operatorId]['parts_completed']++;
            $operatorStats[$operatorId]['board_feet'] += $bf;
            if ($part->run_at && $part->completed_at) {
                $operatorStats[$operatorId]['run_minutes'] += $part->run_at->diffInMinutes($part->completed_at);
            }

            // Material breakdown
            $materialCode = $part->cncProgram->material_code ?? 'Unknown';
            if (!isset($materialStats[$materialCode])) {
                $materialStats[$materialCode] = [
                    'material_code' => $materialCode,
                    'material_name' => CncProgram::getMaterialCodes()[$materialCode] ?? $materialCode,
                    'parts' => 0,
                    'sheets' => 0,
                    'board_feet' => 0,
                ];
            }
            $materialStats[$materialCode]['parts']++;
            $materialStats[$materialCode]['board_feet'] += $bf;
        }

        // Calculate unique sheets per operator
        $partsByOperator = $parts->groupBy('operator_id');
        foreach ($partsByOperator as $operatorId => $operatorParts) {
            $opUniqueSheets = $operatorParts->unique(function ($part) {
                return $part->cnc_program_id . '-' . ($part->sheet_number ?? $part->id);
            })->count();
            if (isset($operatorStats[$operatorId ?? 0])) {
                $operatorStats[$operatorId ?? 0]['sheets_completed'] = $opUniqueSheets;
            }
        }

        // Calculate unique sheets per material
        $partsByMaterial = $parts->groupBy(fn($p) => $p->cncProgram->material_code ?? 'Unknown');
        foreach ($partsByMaterial as $materialCode => $materialParts) {
            $matUniqueSheets = $materialParts->unique(function ($part) {
                return $part->cnc_program_id . '-' . ($part->sheet_number ?? $part->id);
            })->count();
            if (isset($materialStats[$materialCode])) {
                $materialStats[$materialCode]['sheets'] = $matUniqueSheets;
            }
        }

        // Get completed programs for this date
        $programsCompleted = CncProgram::whereDate('updated_at', $date)
            ->where('status', CncProgram::STATUS_COMPLETE)
            ->count();

        // Get average utilization from completed programs
        $utilizationData = CncProgram::whereDate('nested_at', $date)
            ->whereNotNull('utilization_percentage')
            ->selectRaw('AVG(utilization_percentage) as avg_util, SUM(waste_sqft) as total_waste')
            ->first();

        // Calculate throughput metrics
        $avgMinutesPerSheet = $uniqueSheets > 0 ? $totalRunMinutes / $uniqueSheets : null;
        $runHours = $totalRunMinutes / 60;
        $sheetsPerHour = $runHours > 0 ? $uniqueSheets / $runHours : null;
        $bfPerHour = $runHours > 0 ? $totalBoardFeet / $runHours : null;

        // Update the metrics record
        $metrics->update([
            'sheets_completed' => $uniqueSheets,
            'parts_completed' => $parts->count(),
            'board_feet' => round($totalBoardFeet, 2),
            'sqft_processed' => round($totalSqft, 2),
            'utilization_avg' => $utilizationData->avg_util ? round($utilizationData->avg_util, 2) : null,
            'waste_sqft' => round($utilizationData->total_waste ?? 0, 2),
            'operator_breakdown' => array_values($operatorStats),
            'material_breakdown' => array_values($materialStats),
            'total_run_minutes' => $totalRunMinutes,
            'avg_minutes_per_sheet' => $avgMinutesPerSheet ? round($avgMinutesPerSheet, 2) : null,
            'programs_completed' => $programsCompleted,
            'sheets_per_hour' => $sheetsPerHour ? round($sheetsPerHour, 2) : null,
            'bf_per_hour' => $bfPerHour ? round($bfPerHour, 2) : null,
            'computed_at' => now(),
            'is_complete' => $date->lt(Carbon::today()),
        ]);

        Log::info('Production metrics aggregated', [
            'date' => $date->toDateString(),
            'sheets' => $uniqueSheets,
            'parts' => $parts->count(),
            'board_feet' => round($totalBoardFeet, 2),
        ]);

        return $metrics;
    }

    /**
     * Aggregate metrics for a date range
     */
    public function aggregateForDateRange(Carbon $startDate, Carbon $endDate, ?int $companyId = null): array
    {
        $results = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $results[] = $this->aggregateForDate($current->copy(), $companyId);
            $current->addDay();
        }

        return $results;
    }

    /**
     * Backfill historical data
     */
    public function backfill(int $days = 30, ?int $companyId = null): array
    {
        $endDate = Carbon::yesterday();
        $startDate = $endDate->copy()->subDays($days - 1);

        Log::info('Backfilling production metrics', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days' => $days,
        ]);

        return $this->aggregateForDateRange($startDate, $endDate, $companyId);
    }

    /**
     * Calculate board feet for a part from VCarve metadata
     */
    protected function calculatePartBoardFeet(CncProgramPart $part): float
    {
        $metadata = $part->vcarve_metadata;

        if ($metadata && !empty($metadata['material'])) {
            $material = $metadata['material'];
            $thickness = (float) ($material['thickness'] ?? 0.75);
            $width = (float) ($material['width'] ?? 48);
            $height = (float) ($material['height'] ?? 96);

            return $this->calculator->calculateBoardFeet($thickness, $width, $height);
        }

        // Default: Standard 4x8 sheet at 3/4" = ~20 BF
        return self::BF_PER_SHEET;
    }

    /**
     * Calculate square feet for a part
     */
    protected function calculatePartSqft(CncProgramPart $part): float
    {
        $metadata = $part->vcarve_metadata;

        if ($metadata && !empty($metadata['material'])) {
            $material = $metadata['material'];
            $width = (float) ($material['width'] ?? 48);
            $height = (float) ($material['height'] ?? 96);

            return ($width * $height) / 144; // Convert sq inches to sq feet
        }

        // Default: 4x8 sheet = 32 sqft
        return 32;
    }

    /**
     * Check if a date needs re-aggregation
     * (used by observer to debounce updates)
     */
    public function needsReaggregation(Carbon $date): bool
    {
        $metrics = ProductionMetricsDaily::where('metrics_date', $date)->first();

        if (!$metrics) {
            return true;
        }

        // If computed more than 5 minutes ago and day is today, re-aggregate
        if ($date->isToday() && $metrics->computed_at?->lt(now()->subMinutes(5))) {
            return true;
        }

        // If day is not complete but marked as such, re-aggregate
        if ($date->gte(Carbon::today()) && $metrics->is_complete) {
            return true;
        }

        return false;
    }
}
