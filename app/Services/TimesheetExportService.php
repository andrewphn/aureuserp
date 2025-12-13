<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Webkul\Employee\Models\Employee;
use Webkul\Project\Models\Timesheet;

/**
 * Timesheet Export Service
 *
 * Generates timesheet exports in various formats:
 * - CSV for spreadsheet import
 * - HTML for printing
 * - Summary reports for payroll
 *
 * Supports TCS Woodwork schedule: Mon-Thu 8am-5pm (32 hours/week)
 */
class TimesheetExportService
{
    protected ClockingService $clockingService;

    public function __construct(ClockingService $clockingService)
    {
        $this->clockingService = $clockingService;
    }

    /**
     * Export weekly timesheet for a single employee
     */
    public function exportWeeklyTimesheet(
        int $userId,
        ?Carbon $weekStart = null,
        string $format = 'html'
    ): array {
        $weekStart = $weekStart ?? Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->addDays(3); // Mon-Thu

        $employee = Employee::with(['user', 'department', 'job'])
            ->where('user_id', $userId)
            ->first();

        if (!$employee) {
            return ['success' => false, 'error' => 'Employee not found'];
        }

        $entries = Timesheet::forUser($userId)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->orderBy('date')
            ->get();

        $data = $this->buildWeeklyData($employee, $entries, $weekStart);

        return match ($format) {
            'html' => $this->generateHtmlExport($data),
            'csv' => $this->generateCsvExport($data),
            'array' => ['success' => true, 'data' => $data],
            default => ['success' => false, 'error' => 'Invalid format'],
        };
    }

    /**
     * Export weekly summary for all employees
     */
    public function exportTeamWeeklySummary(
        ?Carbon $weekStart = null,
        string $format = 'csv'
    ): array {
        $weekStart = $weekStart ?? Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->addDays(3);

        $employees = Employee::with(['user'])
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->get();

        $summaries = [];

        foreach ($employees as $employee) {
            $summary = $this->clockingService->getWeeklySummary($employee->user_id, $weekStart);
            $summaries[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'department' => $employee->department?->name ?? 'N/A',
                'regular_hours' => min($summary['total_hours'], 32),
                'overtime_hours' => $summary['total_overtime'],
                'total_hours' => $summary['total_hours'],
                'monday' => $summary['daily']['monday']['hours'],
                'tuesday' => $summary['daily']['tuesday']['hours'],
                'wednesday' => $summary['daily']['wednesday']['hours'],
                'thursday' => $summary['daily']['thursday']['hours'],
            ];
        }

        return match ($format) {
            'csv' => $this->generateTeamCsvExport($summaries, $weekStart),
            'array' => ['success' => true, 'data' => $summaries, 'week_start' => $weekStart->format('Y-m-d')],
            default => ['success' => false, 'error' => 'Invalid format'],
        };
    }

    /**
     * Export entries for a date range (for payroll)
     */
    public function exportPayrollReport(
        Carbon $startDate,
        Carbon $endDate,
        ?int $userId = null
    ): array {
        $query = Timesheet::query()
            ->with(['user', 'project'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('user_id')
            ->orderBy('date');

        if ($userId) {
            $query->forUser($userId);
        }

        $entries = $query->get();

        // Group by employee
        $grouped = $entries->groupBy('user_id');

        $report = [];
        foreach ($grouped as $userId => $userEntries) {
            $employee = Employee::where('user_id', $userId)->first();
            $totalHours = $userEntries->sum('unit_amount');
            $regularHours = 0;
            $overtimeHours = 0;

            // Calculate overtime per week
            $weeks = $userEntries->groupBy(fn($e) => $e->date->startOfWeek()->format('Y-W'));
            foreach ($weeks as $weekEntries) {
                $weekTotal = $weekEntries->sum('unit_amount');
                $weekRegular = min($weekTotal, 32);
                $weekOvertime = max(0, $weekTotal - 32);
                $regularHours += $weekRegular;
                $overtimeHours += $weekOvertime;
            }

            $report[] = [
                'employee_name' => $employee?->name ?? 'Unknown',
                'employee_id' => $employee?->id ?? $userId,
                'department' => $employee?->department?->name ?? 'N/A',
                'total_hours' => round($totalHours, 2),
                'regular_hours' => round($regularHours, 2),
                'overtime_hours' => round($overtimeHours, 2),
                'entries_count' => $userEntries->count(),
                'projects' => $userEntries->pluck('project.name')->filter()->unique()->values(),
            ];
        }

        return [
            'success' => true,
            'period' => [
                'start' => $startDate->format('M j, Y'),
                'end' => $endDate->format('M j, Y'),
            ],
            'data' => $report,
            'totals' => [
                'total_hours' => collect($report)->sum('total_hours'),
                'regular_hours' => collect($report)->sum('regular_hours'),
                'overtime_hours' => collect($report)->sum('overtime_hours'),
            ],
        ];
    }

    /**
     * Build weekly data structure for a single employee
     */
    protected function buildWeeklyData(
        Employee $employee,
        Collection $entries,
        Carbon $weekStart
    ): array {
        $weekEnd = $weekStart->copy()->addDays(3);
        $days = [];
        $totalHours = 0;
        $totalOvertime = 0;

        // Build each day's data
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $index => $dayName) {
            $date = $weekStart->copy()->addDays($index);
            $dayEntries = $entries->filter(fn($e) => $e->date->isSameDay($date));

            $hours = $dayEntries->sum('unit_amount');
            $overtime = max(0, $hours - 8);
            $totalHours += $hours;
            $totalOvertime += $overtime;

            // Get clock times from first entry
            $firstEntry = $dayEntries->first();

            $days[$dayName] = [
                'date' => $date->format('m/d/Y'),
                'date_short' => $date->format('M j'),
                'clock_in' => $firstEntry?->getFormattedClockIn() ?? '',
                'clock_out' => $firstEntry?->getFormattedClockOut() ?? '',
                'break_minutes' => $firstEntry?->break_duration_minutes ?? 0,
                'break_formatted' => $firstEntry ? $this->formatMinutes($firstEntry->break_duration_minutes) : '',
                'hours' => $hours,
                'hours_formatted' => $this->formatHours($hours),
                'overtime' => $overtime,
                'overtime_formatted' => $overtime > 0 ? $this->formatHours($overtime) : '',
                'projects' => $dayEntries->pluck('project.name')->filter()->unique()->values()->toArray(),
                'notes' => $dayEntries->pluck('clock_notes')->filter()->values()->toArray(),
            ];
        }

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'department' => $employee->department?->name ?? 'N/A',
                'position' => $employee->job_title ?? $employee->job?->name ?? 'N/A',
            ],
            'period' => [
                'week_start' => $weekStart->format('m/d/Y'),
                'week_end' => $weekEnd->format('m/d/Y'),
                'display' => $weekStart->format('M j') . ' - ' . $weekEnd->format('M j, Y'),
            ],
            'days' => $days,
            'totals' => [
                'regular_hours' => min($totalHours, 32),
                'overtime_hours' => $totalOvertime,
                'total_hours' => $totalHours,
                'regular_formatted' => $this->formatHours(min($totalHours, 32)),
                'overtime_formatted' => $this->formatHours($totalOvertime),
                'total_formatted' => $this->formatHours($totalHours),
            ],
            'generated_at' => Carbon::now()->format('M j, Y g:i A'),
        ];
    }

    /**
     * Generate HTML export from template
     */
    protected function generateHtmlExport(array $data): array
    {
        $templatePath = base_path('templates/timesheets/weekly-timesheet.html');

        if (!file_exists($templatePath)) {
            // Generate basic HTML if template doesn't exist
            return [
                'success' => true,
                'html' => $this->generateBasicHtml($data),
                'filename' => 'timesheet-' . str_replace(' ', '-', strtolower($data['employee']['name'])) . '-' . Carbon::now()->format('Y-m-d') . '.html',
            ];
        }

        $html = file_get_contents($templatePath);
        $html = $this->replaceTemplateValues($html, $data);

        return [
            'success' => true,
            'html' => $html,
            'filename' => 'timesheet-' . str_replace(' ', '-', strtolower($data['employee']['name'])) . '-' . Carbon::now()->format('Y-m-d') . '.html',
        ];
    }

    /**
     * Generate basic HTML without template
     */
    protected function generateBasicHtml(array $data): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Weekly Timesheet - ' . htmlspecialchars($data['employee']['name']) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { color: #d97706; font-size: 24px; }
        .header h2 { font-size: 16px; color: #666; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .info-row label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; }
        th { background: #f3f4f6; font-weight: bold; }
        .totals { background: #fef3c7; font-weight: bold; }
        .overtime { color: #dc2626; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>TCS Woodwork</h1>
        <h2>Weekly Timesheet</h2>
    </div>

    <div class="info-row">
        <div><label>Employee:</label> ' . htmlspecialchars($data['employee']['name']) . '</div>
        <div><label>Department:</label> ' . htmlspecialchars($data['employee']['department']) . '</div>
    </div>
    <div class="info-row">
        <div><label>Position:</label> ' . htmlspecialchars($data['employee']['position']) . '</div>
        <div><label>Pay Period:</label> ' . htmlspecialchars($data['period']['display']) . '</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Day</th>
                <th>Date</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Break</th>
                <th>Hours</th>
                <th>OT</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($data['days'] as $dayName => $day) {
            $html .= '
            <tr>
                <td>' . $dayName . '</td>
                <td>' . $day['date'] . '</td>
                <td>' . ($day['clock_in'] ?: '-') . '</td>
                <td>' . ($day['clock_out'] ?: '-') . '</td>
                <td>' . ($day['break_formatted'] ?: '-') . '</td>
                <td>' . ($day['hours'] > 0 ? $day['hours_formatted'] : '-') . '</td>
                <td class="overtime">' . ($day['overtime'] > 0 ? $day['overtime_formatted'] : '-') . '</td>
            </tr>';
        }

        $html .= '
            <tr class="totals">
                <td colspan="5"><strong>Weekly Totals</strong></td>
                <td><strong>' . $data['totals']['total_formatted'] . '</strong></td>
                <td class="overtime"><strong>' . ($data['totals']['overtime_hours'] > 0 ? $data['totals']['overtime_formatted'] : '-') . '</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="info-row">
        <div><label>Regular Hours:</label> ' . $data['totals']['regular_formatted'] . '</div>
        <div><label>Overtime Hours:</label> ' . $data['totals']['overtime_formatted'] . '</div>
        <div><label>Total Hours:</label> ' . $data['totals']['total_formatted'] . '</div>
    </div>

    <div style="margin-top: 40px;">
        <div class="info-row">
            <div style="width: 45%;">
                <label>Employee Signature:</label>
                <div style="border-bottom: 1px solid #333; height: 30px; margin-top: 5px;"></div>
            </div>
            <div style="width: 45%;">
                <label>Date:</label>
                <div style="border-bottom: 1px solid #333; height: 30px; margin-top: 5px;"></div>
            </div>
        </div>
        <div class="info-row" style="margin-top: 20px;">
            <div style="width: 45%;">
                <label>Supervisor Signature:</label>
                <div style="border-bottom: 1px solid #333; height: 30px; margin-top: 5px;"></div>
            </div>
            <div style="width: 45%;">
                <label>Date:</label>
                <div style="border-bottom: 1px solid #333; height: 30px; margin-top: 5px;"></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Generated: ' . $data['generated_at'] . ' | TCS Woodwork - Mon-Thu 8am-5pm | Weekly Target: 32 hours</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Generate CSV export for a single employee
     */
    protected function generateCsvExport(array $data): array
    {
        $csv = "Weekly Timesheet Export\n";
        $csv .= "Employee," . $data['employee']['name'] . "\n";
        $csv .= "Department," . $data['employee']['department'] . "\n";
        $csv .= "Period," . $data['period']['display'] . "\n\n";
        $csv .= "Day,Date,Clock In,Clock Out,Break,Hours,Overtime,Projects\n";

        foreach ($data['days'] as $dayName => $day) {
            $csv .= implode(',', [
                $dayName,
                $day['date'],
                $day['clock_in'] ?: '',
                $day['clock_out'] ?: '',
                $day['break_formatted'] ?: '',
                $day['hours'],
                $day['overtime'],
                '"' . implode('; ', $day['projects']) . '"',
            ]) . "\n";
        }

        $csv .= "\nTotals\n";
        $csv .= "Regular Hours," . $data['totals']['regular_hours'] . "\n";
        $csv .= "Overtime Hours," . $data['totals']['overtime_hours'] . "\n";
        $csv .= "Total Hours," . $data['totals']['total_hours'] . "\n";

        return [
            'success' => true,
            'csv' => $csv,
            'filename' => 'timesheet-' . str_replace(' ', '-', strtolower($data['employee']['name'])) . '-' . Carbon::now()->format('Y-m-d') . '.csv',
        ];
    }

    /**
     * Generate team CSV export
     */
    protected function generateTeamCsvExport(array $summaries, Carbon $weekStart): array
    {
        $csv = "Team Weekly Timesheet Summary\n";
        $csv .= "Week of " . $weekStart->format('M j, Y') . "\n\n";
        $csv .= "Employee,Department,Monday,Tuesday,Wednesday,Thursday,Regular Hours,Overtime,Total\n";

        $totalRegular = 0;
        $totalOvertime = 0;
        $totalAll = 0;

        foreach ($summaries as $summary) {
            $csv .= implode(',', [
                '"' . $summary['employee_name'] . '"',
                '"' . $summary['department'] . '"',
                $summary['monday'],
                $summary['tuesday'],
                $summary['wednesday'],
                $summary['thursday'],
                $summary['regular_hours'],
                $summary['overtime_hours'],
                $summary['total_hours'],
            ]) . "\n";

            $totalRegular += $summary['regular_hours'];
            $totalOvertime += $summary['overtime_hours'];
            $totalAll += $summary['total_hours'];
        }

        $csv .= "\nTeam Totals,," . count($summaries) . " employees,,,," . $totalRegular . "," . $totalOvertime . "," . $totalAll . "\n";

        return [
            'success' => true,
            'csv' => $csv,
            'filename' => 'team-timesheet-' . $weekStart->format('Y-m-d') . '.csv',
        ];
    }

    /**
     * Replace template placeholders with data
     */
    protected function replaceTemplateValues(string $html, array $data): string
    {
        $replacements = [
            '{{EMPLOYEE_NAME}}' => $data['employee']['name'],
            '{{EMPLOYEE_ID}}' => 'EMP-' . str_pad($data['employee']['id'], 4, '0', STR_PAD_LEFT),
            '{{DEPARTMENT}}' => $data['employee']['department'],
            '{{POSITION}}' => $data['employee']['position'],
            '{{PAY_PERIOD}}' => $data['period']['display'],
            '{{WEEK_START}}' => $data['period']['week_start'],
            '{{WEEK_END}}' => $data['period']['week_end'],
            '{{REGULAR_HOURS}}' => $data['totals']['regular_formatted'],
            '{{OVERTIME_HOURS}}' => $data['totals']['overtime_formatted'],
            '{{TOTAL_HOURS}}' => $data['totals']['total_formatted'],
            '{{GENERATED_DATE}}' => $data['generated_at'],
        ];

        // Replace day-specific values
        foreach ($data['days'] as $dayName => $day) {
            $prefix = '{{' . strtoupper($dayName) . '_';
            $replacements[$prefix . 'DATE}}'] = $day['date'];
            $replacements[$prefix . 'IN}}'] = $day['clock_in'] ?: '';
            $replacements[$prefix . 'OUT}}'] = $day['clock_out'] ?: '';
            $replacements[$prefix . 'BREAK}}'] = $day['break_formatted'] ?: '';
            $replacements[$prefix . 'HOURS}}'] = $day['hours'] > 0 ? $day['hours_formatted'] : '';
            $replacements[$prefix . 'OT}}'] = $day['overtime'] > 0 ? $day['overtime_formatted'] : '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    /**
     * Format hours for display
     */
    protected function formatHours(float $hours): string
    {
        if ($hours == 0) {
            return '0h';
        }

        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);

        if ($minutes > 0) {
            return "{$wholeHours}h {$minutes}m";
        }

        return "{$wholeHours}h";
    }

    /**
     * Format minutes for display
     */
    protected function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}m";
        }

        return "{$hours}h";
    }
}
