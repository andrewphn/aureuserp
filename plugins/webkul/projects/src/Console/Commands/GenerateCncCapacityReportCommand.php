<?php

namespace Webkul\Project\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Webkul\Project\Services\CncCapacityAnalyticsService;

/**
 * Generate CNC Capacity Report
 *
 * Generates a comprehensive capacity report showing board feet production
 * from VCarve data. Can output to console or export to CSV.
 *
 * Usage:
 *   php artisan cnc:capacity-report
 *   php artisan cnc:capacity-report --start=2025-09-01 --end=2026-01-31
 *   php artisan cnc:capacity-report --export=capacity-report.csv
 */
class GenerateCncCapacityReportCommand extends Command
{
    protected $signature = 'cnc:capacity-report
        {--start= : Start date (YYYY-MM-DD), defaults to 90 days ago}
        {--end= : End date (YYYY-MM-DD), defaults to today}
        {--export= : Export to CSV file path}';

    protected $description = 'Generate CNC board feet capacity report from VCarve data';

    protected CncCapacityAnalyticsService $analyticsService;

    public function __construct(CncCapacityAnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    public function handle(): int
    {
        $endDate = $this->option('end')
            ? Carbon::parse($this->option('end'))
            : Carbon::today();

        $startDate = $this->option('start')
            ? Carbon::parse($this->option('start'))
            : $endDate->copy()->subDays(90);

        $this->info("Generating CNC Capacity Report");
        $this->info("Period: {$startDate->toDateString()} to {$endDate->toDateString()}");
        $this->newLine();

        $report = $this->analyticsService->getCapacityReport($startDate, $endDate);

        // Summary section
        $this->info('=== SUMMARY ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Board Feet', number_format($report['summary']['total_board_feet'], 2)],
                ['Total Sheets', number_format($report['summary']['total_sheets'])],
                ['Working Days', $report['summary']['working_days']],
                ['Avg BF/Day', number_format($report['summary']['average_bf_per_day'], 2)],
                ['Avg Sheets/Day', number_format($report['summary']['average_sheets_per_day'], 1)],
            ]
        );

        // Peak days
        $this->newLine();
        $this->info('=== TOP 5 PRODUCTION DAYS ===');
        $peakRows = [];
        foreach (array_slice($report['peak_days'], 0, 5) as $peak) {
            $peakRows[] = [
                $peak['date'],
                $peak['day_of_week'],
                $peak['sheet_count'],
                number_format($peak['board_feet'], 2),
            ];
        }
        $this->table(['Date', 'Day', 'Sheets', 'Board Feet'], $peakRows);

        // Material breakdown
        $this->newLine();
        $this->info('=== MATERIAL BREAKDOWN ===');
        $materialRows = [];
        foreach ($report['material_breakdown']['materials'] as $material) {
            $materialRows[] = [
                $material['code'],
                $material['sheets'],
                number_format($material['board_feet'], 2),
                $material['percentage'] . '%',
                number_format($material['average_bf_per_sheet'], 2),
            ];
        }
        $this->table(['Material', 'Sheets', 'Board Feet', '%', 'Avg BF/Sheet'], $materialRows);

        // Monthly trend
        if (!empty($report['monthly_totals'])) {
            $this->newLine();
            $this->info('=== MONTHLY TOTALS ===');
            $monthlyRows = [];
            foreach ($report['monthly_totals'] as $month) {
                $monthlyRows[] = [
                    $month['month_name'],
                    $month['working_days'],
                    $month['total_sheets'],
                    number_format($month['total_board_feet'], 2),
                    number_format($month['avg_bf_per_day'], 2),
                ];
            }
            $this->table(['Month', 'Work Days', 'Sheets', 'Board Feet', 'Avg BF/Day'], $monthlyRows);
        }

        // Estimate accuracy
        if (!empty($report['estimate_accuracy']['has_data'])) {
            $this->newLine();
            $this->info('=== ESTIMATE ACCURACY ===');
            $est = $report['estimate_accuracy'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Programs Compared', $est['programs_compared']],
                    ['Total Estimated Sheets', $est['total_estimated_sheets']],
                    ['Total Actual Sheets', $est['total_actual_sheets']],
                    ['Variance', $est['total_variance'] . ' sheets (' . $est['variance_percentage'] . '%)'],
                    ['Accuracy Rate', $est['accuracy_rate'] . '% (within 1 sheet)'],
                    ['Avg Utilization', $est['average_utilization'] . '%'],
                ]
            );
        }

        // Export to CSV if requested
        if ($exportPath = $this->option('export')) {
            $this->exportToCsv($report, $exportPath);
            $this->newLine();
            $this->info("Report exported to: {$exportPath}");
        }

        return self::SUCCESS;
    }

    protected function exportToCsv(array $report, string $path): void
    {
        $fp = fopen($path, 'w');

        // Summary
        fputcsv($fp, ['CNC Capacity Report']);
        fputcsv($fp, ['Period', $report['period']['start'], 'to', $report['period']['end']]);
        fputcsv($fp, []);

        fputcsv($fp, ['SUMMARY']);
        fputcsv($fp, ['Total Board Feet', $report['summary']['total_board_feet']]);
        fputcsv($fp, ['Total Sheets', $report['summary']['total_sheets']]);
        fputcsv($fp, ['Working Days', $report['summary']['working_days']]);
        fputcsv($fp, ['Avg BF/Day', $report['summary']['average_bf_per_day']]);
        fputcsv($fp, ['Avg Sheets/Day', $report['summary']['average_sheets_per_day']]);
        fputcsv($fp, []);

        // Material breakdown
        fputcsv($fp, ['MATERIAL BREAKDOWN']);
        fputcsv($fp, ['Code', 'Type', 'Sheets', 'Board Feet', 'Percentage', 'Avg BF/Sheet']);
        foreach ($report['material_breakdown']['materials'] as $material) {
            fputcsv($fp, [
                $material['code'],
                $material['type'],
                $material['sheets'],
                $material['board_feet'],
                $material['percentage'],
                $material['average_bf_per_sheet'],
            ]);
        }
        fputcsv($fp, []);

        // Daily data
        fputcsv($fp, ['DAILY BREAKDOWN']);
        fputcsv($fp, ['Date', 'Sheets', 'Board Feet', 'Avg BF/Sheet']);
        foreach ($report['daily_data'] as $day) {
            fputcsv($fp, [
                $day['date'],
                $day['total_sheets'],
                $day['total_board_feet'],
                $day['average_bf_per_sheet'],
            ]);
        }

        fclose($fp);
    }
}
