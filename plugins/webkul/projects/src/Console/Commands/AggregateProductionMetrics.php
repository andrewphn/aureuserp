<?php

namespace Webkul\Project\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Webkul\Project\Services\ProductionMetricsAggregationService;

/**
 * Aggregate Production Metrics Command
 *
 * Artisan command to aggregate CNC production metrics.
 *
 * Usage:
 *   php artisan production:aggregate                  # Aggregate yesterday
 *   php artisan production:aggregate --date=2026-01-15  # Specific date
 *   php artisan production:aggregate --backfill=30    # Backfill 30 days
 *   php artisan production:aggregate --today          # Force today
 */
class AggregateProductionMetrics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'production:aggregate
                            {--date= : Specific date to aggregate (Y-m-d format)}
                            {--backfill= : Number of days to backfill}
                            {--today : Include today in aggregation}
                            {--company= : Company ID for multi-tenant}';

    /**
     * The console command description.
     */
    protected $description = 'Aggregate daily production metrics from CNC data';

    /**
     * Execute the console command.
     */
    public function handle(ProductionMetricsAggregationService $service): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;

        // Handle backfill option
        if ($this->option('backfill')) {
            $days = (int) $this->option('backfill');
            $this->info("Backfilling {$days} days of production metrics...");

            $results = $service->backfill($days, $companyId);

            $this->info("Aggregated {$days} days of metrics.");
            $this->displaySummary($results);

            return self::SUCCESS;
        }

        // Handle specific date option
        if ($this->option('date')) {
            $date = Carbon::parse($this->option('date'));
            $this->info("Aggregating metrics for {$date->toDateString()}...");

            $metrics = $service->aggregateForDate($date, $companyId);

            $this->displayMetrics($metrics);

            return self::SUCCESS;
        }

        // Default: aggregate yesterday (and optionally today)
        $dates = [Carbon::yesterday()];

        if ($this->option('today')) {
            $dates[] = Carbon::today();
        }

        foreach ($dates as $date) {
            $this->info("Aggregating metrics for {$date->toDateString()}...");
            $metrics = $service->aggregateForDate($date, $companyId);
            $this->displayMetrics($metrics);
        }

        return self::SUCCESS;
    }

    /**
     * Display metrics for a single day
     */
    protected function displayMetrics($metrics): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Date', $metrics->metrics_date->toDateString()],
                ['Sheets Completed', $metrics->sheets_completed],
                ['Parts Completed', $metrics->parts_completed],
                ['Board Feet', number_format($metrics->board_feet, 2)],
                ['Sq Ft Processed', number_format($metrics->sqft_processed, 2)],
                ['Avg Utilization', $metrics->utilization_avg ? $metrics->utilization_avg . '%' : 'N/A'],
                ['Waste (sqft)', number_format($metrics->waste_sqft, 2)],
                ['Total Run Time', $metrics->total_run_minutes . ' min'],
                ['Programs Completed', $metrics->programs_completed],
                ['Sheets/Hour', $metrics->sheets_per_hour ? number_format($metrics->sheets_per_hour, 2) : 'N/A'],
                ['BF/Hour', $metrics->bf_per_hour ? number_format($metrics->bf_per_hour, 2) : 'N/A'],
                ['Is Complete', $metrics->is_complete ? 'Yes' : 'No'],
            ]
        );

        if ($metrics->operator_breakdown) {
            $this->newLine();
            $this->info('Operator Breakdown:');
            $this->table(
                ['Operator', 'Parts', 'Sheets', 'Board Feet', 'Run Time'],
                collect($metrics->operator_breakdown)->map(function ($op) {
                    return [
                        $op['operator_name'],
                        $op['parts_completed'],
                        $op['sheets_completed'],
                        number_format($op['board_feet'], 2),
                        $op['run_minutes'] . ' min',
                    ];
                })->toArray()
            );
        }

        if ($metrics->material_breakdown) {
            $this->newLine();
            $this->info('Material Breakdown:');
            $this->table(
                ['Material', 'Parts', 'Sheets', 'Board Feet'],
                collect($metrics->material_breakdown)->map(function ($mat) {
                    return [
                        $mat['material_name'],
                        $mat['parts'],
                        $mat['sheets'],
                        number_format($mat['board_feet'], 2),
                    ];
                })->toArray()
            );
        }
    }

    /**
     * Display summary for multiple days
     */
    protected function displaySummary(array $results): void
    {
        $totalSheets = collect($results)->sum('sheets_completed');
        $totalParts = collect($results)->sum('parts_completed');
        $totalBf = collect($results)->sum('board_feet');
        $workingDays = collect($results)->filter(fn($m) => $m->sheets_completed > 0)->count();

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Days Processed', count($results)],
                ['Working Days', $workingDays],
                ['Total Sheets', $totalSheets],
                ['Total Parts', $totalParts],
                ['Total Board Feet', number_format($totalBf, 2)],
                ['Avg Sheets/Day', $workingDays > 0 ? number_format($totalSheets / $workingDays, 1) : 'N/A'],
                ['Avg BF/Day', $workingDays > 0 ? number_format($totalBf / $workingDays, 2) : 'N/A'],
            ]
        );
    }
}
