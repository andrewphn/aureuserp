<?php

namespace Webkul\Timesheet\Filament\Widgets;

use App\Services\ClockingService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Webkul\Employee\Models\Employee;
use Webkul\Project\Models\Timesheet;

/**
 * Weekly Hours Report Widget
 *
 * Shows weekly hours per employee with daily breakdown (Mon-Thu),
 * overtime highlighting, and missing punch alerts.
 * TCS Schedule: Mon-Thu 8am-5pm, 32 hours/week target.
 */
class WeeklyHoursReportWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public ?Carbon $weekStart = null;

    public function mount(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek();
    }

    public function getTableHeading(): ?string
    {
        $start = $this->weekStart ?? Carbon::now()->startOfWeek();
        $end = $start->copy()->addDays(3);

        return "Weekly Hours Report ({$start->format('M j')} - {$end->format('M j, Y')})";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->where('is_active', true)
                    ->whereNotNull('user_id')
            )
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('monday_hours')
                    ->label('Mon')
                    ->state(fn (Employee $record): string => $this->getDayHours($record, 0))
                    ->color(fn (Employee $record): ?string => $this->getDayColor($record, 0))
                    ->alignCenter(),

                TextColumn::make('tuesday_hours')
                    ->label('Tue')
                    ->state(fn (Employee $record): string => $this->getDayHours($record, 1))
                    ->color(fn (Employee $record): ?string => $this->getDayColor($record, 1))
                    ->alignCenter(),

                TextColumn::make('wednesday_hours')
                    ->label('Wed')
                    ->state(fn (Employee $record): string => $this->getDayHours($record, 2))
                    ->color(fn (Employee $record): ?string => $this->getDayColor($record, 2))
                    ->alignCenter(),

                TextColumn::make('thursday_hours')
                    ->label('Thu')
                    ->state(fn (Employee $record): string => $this->getDayHours($record, 3))
                    ->color(fn (Employee $record): ?string => $this->getDayColor($record, 3))
                    ->alignCenter(),

                TextColumn::make('total_hours')
                    ->label('Total')
                    ->state(fn (Employee $record): string => $this->getTotalHours($record))
                    ->weight('bold')
                    ->alignCenter(),

                TextColumn::make('overtime_hours')
                    ->label('OT')
                    ->state(fn (Employee $record): string => $this->getOvertimeHours($record))
                    ->color(fn (Employee $record): ?string => $this->getOvertimeColor($record))
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (Employee $record): string => $this->getStatusText($record))
                    ->badge()
                    ->color(fn (Employee $record): string => $this->getStatusColor($record)),
            ])
            ->filters([
                Filter::make('week_select')
                    ->form([
                        DatePicker::make('week_of')
                            ->label('Week Of')
                            ->default(Carbon::now()->startOfWeek())
                            ->displayFormat('M j, Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['week_of']) {
                            $this->weekStart = Carbon::parse($data['week_of'])->startOfWeek();
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['week_of']) {
                            return 'Week of ' . Carbon::parse($data['week_of'])->startOfWeek()->format('M j');
                        }
                        return null;
                    }),
            ])
            ->headerActions([
                Action::make('previous_week')
                    ->label('Previous Week')
                    ->icon('heroicon-o-chevron-left')
                    ->action(function () {
                        $this->weekStart = ($this->weekStart ?? Carbon::now()->startOfWeek())->subWeek();
                    }),
                Action::make('current_week')
                    ->label('Current Week')
                    ->icon('heroicon-o-calendar')
                    ->action(function () {
                        $this->weekStart = Carbon::now()->startOfWeek();
                    }),
                Action::make('next_week')
                    ->label('Next Week')
                    ->icon('heroicon-o-chevron-right')
                    ->action(function () {
                        $this->weekStart = ($this->weekStart ?? Carbon::now()->startOfWeek())->addWeek();
                    }),
                Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => $this->exportToCsv()),
            ])
            ->paginated(false);
    }

    /**
     * Get weekly summary for an employee (cached per request)
     */
    protected function getWeeklySummary(Employee $employee): array
    {
        static $cache = [];

        $key = $employee->user_id . '-' . ($this->weekStart?->format('Y-m-d') ?? 'current');

        if (!isset($cache[$key])) {
            $clockingService = app(ClockingService::class);
            $cache[$key] = $clockingService->getWeeklySummary(
                $employee->user_id,
                $this->weekStart
            );
        }

        return $cache[$key];
    }

    /**
     * Get hours for a specific day
     */
    protected function getDayHours(Employee $record, int $dayIndex): string
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday'];
        $summary = $this->getWeeklySummary($record);

        $dayData = $summary['daily'][$days[$dayIndex]] ?? null;

        if (!$dayData || $dayData['hours'] == 0) {
            // Check if day is in the future
            $dayDate = ($this->weekStart ?? Carbon::now()->startOfWeek())->copy()->addDays($dayIndex);
            if ($dayDate->isFuture()) {
                return '-';
            }
            return '0h';
        }

        return $dayData['formatted'];
    }

    /**
     * Get color for a specific day based on hours
     */
    protected function getDayColor(Employee $record, int $dayIndex): ?string
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday'];
        $summary = $this->getWeeklySummary($record);

        $dayData = $summary['daily'][$days[$dayIndex]] ?? null;

        if (!$dayData) {
            return null;
        }

        // Daily overtime (>8 hours)
        if ($dayData['hours'] > 8) {
            return 'danger';
        }

        // Missing punch (day passed but no hours)
        $dayDate = ($this->weekStart ?? Carbon::now()->startOfWeek())->copy()->addDays($dayIndex);
        if ($dayDate->isPast() && !$dayDate->isToday() && $dayData['hours'] == 0) {
            return 'warning';
        }

        return null;
    }

    /**
     * Get total hours for the week
     */
    protected function getTotalHours(Employee $record): string
    {
        $summary = $this->getWeeklySummary($record);
        return $summary['total_formatted'] . ' / 32h';
    }

    /**
     * Get overtime hours
     */
    protected function getOvertimeHours(Employee $record): string
    {
        $summary = $this->getWeeklySummary($record);
        $overtime = $summary['total_overtime'] ?? 0;

        if ($overtime <= 0) {
            return '-';
        }

        return $this->formatHours($overtime);
    }

    /**
     * Get color for overtime display
     */
    protected function getOvertimeColor(Employee $record): ?string
    {
        $summary = $this->getWeeklySummary($record);
        $overtime = $summary['total_overtime'] ?? 0;

        if ($overtime > 0) {
            return 'danger';
        }

        return null;
    }

    /**
     * Get status text for employee
     */
    protected function getStatusText(Employee $record): string
    {
        $summary = $this->getWeeklySummary($record);

        // Check for missing punches
        $missingDays = $this->countMissingDays($summary);
        if ($missingDays > 0) {
            return "Missing {$missingDays} day(s)";
        }

        // Check if on track
        if ($summary['on_track']) {
            if ($summary['total_hours'] >= 32) {
                return 'Complete';
            }
            return 'On Track';
        }

        // Behind schedule
        $remaining = $summary['remaining'] ?? 0;
        if ($remaining > 0) {
            return $this->formatHours($remaining) . ' remaining';
        }

        return 'On Track';
    }

    /**
     * Get color for status badge
     */
    protected function getStatusColor(Employee $record): string
    {
        $summary = $this->getWeeklySummary($record);

        // Check for missing punches
        $missingDays = $this->countMissingDays($summary);
        if ($missingDays > 0) {
            return 'warning';
        }

        // Complete
        if ($summary['total_hours'] >= 32) {
            return 'success';
        }

        // On track
        if ($summary['on_track']) {
            return 'success';
        }

        // Behind
        return 'gray';
    }

    /**
     * Count days with missing punches (past working days with 0 hours)
     */
    protected function countMissingDays(array $summary): int
    {
        $missing = 0;
        $days = ['monday', 'tuesday', 'wednesday', 'thursday'];
        $weekStart = $this->weekStart ?? Carbon::now()->startOfWeek();

        foreach ($days as $index => $day) {
            $dayDate = $weekStart->copy()->addDays($index);
            $dayData = $summary['daily'][$day] ?? null;

            // Only count past days (not today or future)
            if ($dayDate->isPast() && !$dayDate->isToday()) {
                if (!$dayData || $dayData['hours'] == 0) {
                    $missing++;
                }
            }
        }

        return $missing;
    }

    /**
     * Format hours for display
     */
    protected function formatHours(float $hours): string
    {
        if ($hours === 0.0) {
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
     * Export weekly hours to CSV
     */
    public function exportToCsv()
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->orderBy('name')
            ->get();

        $weekStart = $this->weekStart ?? Carbon::now()->startOfWeek();
        $filename = 'weekly-hours-' . $weekStart->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees, $weekStart) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Employee',
                'Monday ' . $weekStart->format('M j'),
                'Tuesday ' . $weekStart->copy()->addDay()->format('M j'),
                'Wednesday ' . $weekStart->copy()->addDays(2)->format('M j'),
                'Thursday ' . $weekStart->copy()->addDays(3)->format('M j'),
                'Total Hours',
                'Overtime',
                'Target',
                'Status'
            ]);

            $clockingService = app(ClockingService::class);

            foreach ($employees as $employee) {
                $summary = $clockingService->getWeeklySummary($employee->user_id, $weekStart);

                fputcsv($file, [
                    $employee->name,
                    $summary['daily']['monday']['hours'] ?? 0,
                    $summary['daily']['tuesday']['hours'] ?? 0,
                    $summary['daily']['wednesday']['hours'] ?? 0,
                    $summary['daily']['thursday']['hours'] ?? 0,
                    $summary['total_hours'],
                    $summary['total_overtime'],
                    Timesheet::WEEKLY_HOURS_TARGET,
                    $summary['on_track'] ? 'On Track' : 'Behind',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
