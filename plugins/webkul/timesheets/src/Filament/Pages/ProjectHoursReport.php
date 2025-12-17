<?php

namespace Webkul\Timesheet\Filament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Timesheet;

/**
 * Project Labor Hours Report Page
 *
 * Shows total labor hours per project with employee breakdown,
 * actual vs. estimated hours comparison, and cost analysis.
 */
class ProjectHoursReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'timesheets::filament.pages.project-hours-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 10;

    // Filter properties
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedProjectId = null;

    // Drill-down state
    public ?int $expandedProjectId = null;

    // Default hourly rate for cost calculation
    public const DEFAULT_HOURLY_RATE = 35.00;

    public function mount(): void
    {
        // Default to current month
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public static function getNavigationLabel(): string
    {
        return 'Project Labor Report';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Project';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Project Labor Hours Report';
    }

    public function getSubheading(): ?string
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->format('M j, Y');
            $end = Carbon::parse($this->endDate)->format('M j, Y');
            return "Showing data from {$start} to {$end}";
        }
        return null;
    }

    /**
     * Determine if the page can be accessed.
     */
    public static function canAccess(): bool
    {
        return true;
    }

    /**
     * Filter Form
     */
    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('startDate')
                    ->label('Start Date')
                    ->default(Carbon::now()->startOfMonth())
                    ->native(false)
                    ->live(),
                DatePicker::make('endDate')
                    ->label('End Date')
                    ->default(Carbon::now()->endOfMonth())
                    ->native(false)
                    ->live(),
                Select::make('selectedProjectId')
                    ->label('Filter by Project')
                    ->options(fn () => Project::pluck('name', 'id'))
                    ->placeholder('All Projects')
                    ->searchable()
                    ->preload()
                    ->live(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    /**
     * Main Table - Project Summary
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getProjectsQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Project $record): ?string => $record->partner?->name),

                TextColumn::make('total_hours')
                    ->label('Actual Hours')
                    ->state(fn (Project $record): string => $this->formatHours($this->getProjectHours($record->id)))
                    ->alignCenter()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("(SELECT SUM(unit_amount) FROM analytic_records WHERE project_id = projects_projects.id) {$direction}");
                    }),

                TextColumn::make('allocated_hours')
                    ->label('Allocated Hours')
                    ->state(fn (Project $record): string => $this->formatHours($record->allocated_hours ?? 0))
                    ->alignCenter()
                    ->color('gray'),

                TextColumn::make('variance')
                    ->label('Variance')
                    ->state(fn (Project $record): string => $this->getVariance($record))
                    ->color(fn (Project $record): string => $this->getVarianceColor($record))
                    ->alignCenter(),

                TextColumn::make('progress')
                    ->label('% Complete')
                    ->state(fn (Project $record): string => $this->getProgressPercentage($record))
                    ->alignCenter(),

                TextColumn::make('labor_cost')
                    ->label('Labor Cost')
                    ->state(fn (Project $record): string => $this->getLaborCost($record))
                    ->money('USD')
                    ->alignEnd(),

                TextColumn::make('employee_count')
                    ->label('Employees')
                    ->state(fn (Project $record): int => $this->getEmployeeCount($record->id))
                    ->alignCenter(),
            ])
            ->defaultSort('total_hours', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->recordAction('toggleExpand')
            ->recordClasses(fn (Project $record): string =>
                $this->expandedProjectId === $record->id ? 'bg-primary-50' : ''
            );
    }

    /**
     * Get projects query with date filtering
     */
    protected function getProjectsQuery(): Builder
    {
        $query = Project::query()
            ->whereHas('timesheets', function (Builder $q) {
                if ($this->startDate) {
                    $q->whereDate('date', '>=', $this->startDate);
                }
                if ($this->endDate) {
                    $q->whereDate('date', '<=', $this->endDate);
                }
            });

        if ($this->selectedProjectId) {
            $query->where('id', $this->selectedProjectId);
        }

        return $query;
    }

    /**
     * Get total hours for a project within date range
     */
    protected function getProjectHours(int $projectId): float
    {
        $query = Timesheet::where('project_id', $projectId);

        if ($this->startDate) {
            $query->whereDate('date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('date', '<=', $this->endDate);
        }

        return $query->sum('unit_amount') ?? 0;
    }

    /**
     * Get variance (actual - allocated)
     */
    protected function getVariance(Project $project): string
    {
        $actual = $this->getProjectHours($project->id);
        $allocated = $project->allocated_hours ?? 0;

        if ($allocated == 0) {
            return '-';
        }

        $variance = $actual - $allocated;
        $sign = $variance > 0 ? '+' : '';

        return $sign . $this->formatHours($variance);
    }

    /**
     * Get variance color
     */
    protected function getVarianceColor(Project $project): string
    {
        $actual = $this->getProjectHours($project->id);
        $allocated = $project->allocated_hours ?? 0;

        if ($allocated == 0) {
            return 'gray';
        }

        $variance = $actual - $allocated;

        if ($variance > 0) {
            return 'danger'; // Over budget
        } elseif ($variance < 0) {
            return 'success'; // Under budget
        }

        return 'gray';
    }

    /**
     * Get progress percentage
     */
    protected function getProgressPercentage(Project $project): string
    {
        $actual = $this->getProjectHours($project->id);
        $allocated = $project->allocated_hours ?? 0;

        if ($allocated == 0) {
            return '-';
        }

        $percentage = ($actual / $allocated) * 100;

        return round($percentage, 1) . '%';
    }

    /**
     * Get labor cost
     */
    protected function getLaborCost(Project $project): float
    {
        $hours = $this->getProjectHours($project->id);
        return $hours * self::DEFAULT_HOURLY_RATE;
    }

    /**
     * Get unique employee count for project
     */
    protected function getEmployeeCount(int $projectId): int
    {
        $query = Timesheet::where('project_id', $projectId);

        if ($this->startDate) {
            $query->whereDate('date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('date', '<=', $this->endDate);
        }

        return $query->distinct('user_id')->count('user_id');
    }

    /**
     * Toggle project expansion for drill-down
     */
    public function toggleExpand(Project $record): void
    {
        if ($this->expandedProjectId === $record->id) {
            $this->expandedProjectId = null;
        } else {
            $this->expandedProjectId = $record->id;
        }
    }

    /**
     * Get employee breakdown for expanded project
     */
    public function getEmployeeBreakdown(): array
    {
        if (!$this->expandedProjectId) {
            return [];
        }

        $query = Timesheet::where('project_id', $this->expandedProjectId)
            ->select('user_id', DB::raw('SUM(unit_amount) as total_hours'))
            ->with('user')
            ->groupBy('user_id');

        if ($this->startDate) {
            $query->whereDate('date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('date', '<=', $this->endDate);
        }

        return $query->get()->map(function ($item) {
            return [
                'employee' => $item->user?->name ?? 'Unknown',
                'hours' => $this->formatHours($item->total_hours),
                'hours_decimal' => $item->total_hours,
                'cost' => number_format($item->total_hours * self::DEFAULT_HOURLY_RATE, 2),
            ];
        })->toArray();
    }

    /**
     * Get task breakdown for expanded project
     */
    public function getTaskBreakdown(): array
    {
        if (!$this->expandedProjectId) {
            return [];
        }

        $query = Timesheet::where('project_id', $this->expandedProjectId)
            ->whereNotNull('task_id')
            ->select('task_id', DB::raw('SUM(unit_amount) as total_hours'))
            ->with('task')
            ->groupBy('task_id');

        if ($this->startDate) {
            $query->whereDate('date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('date', '<=', $this->endDate);
        }

        return $query->get()->map(function ($item) {
            return [
                'task' => $item->task?->name ?? 'No Task',
                'hours' => $this->formatHours($item->total_hours),
                'hours_decimal' => $item->total_hours,
                'allocated' => $this->formatHours($item->task?->allocated_hours ?? 0),
            ];
        })->toArray();
    }

    /**
     * Get summary totals
     */
    public function getSummaryTotals(): array
    {
        $query = Timesheet::query();

        if ($this->startDate) {
            $query->whereDate('date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('date', '<=', $this->endDate);
        }
        if ($this->selectedProjectId) {
            $query->where('project_id', $this->selectedProjectId);
        }

        $totalHours = $query->sum('unit_amount') ?? 0;
        $totalProjects = $query->distinct('project_id')->count('project_id');
        $totalEmployees = $query->distinct('user_id')->count('user_id');
        $totalCost = $totalHours * self::DEFAULT_HOURLY_RATE;

        return [
            'total_hours' => $this->formatHours($totalHours),
            'total_hours_decimal' => $totalHours,
            'total_projects' => $totalProjects,
            'total_employees' => $totalEmployees,
            'total_cost' => number_format($totalCost, 2),
        ];
    }

    /**
     * Format hours for display
     */
    protected function formatHours(float $hours): string
    {
        if ($hours === 0.0) {
            return '0h';
        }

        $absHours = abs($hours);
        $sign = $hours < 0 ? '-' : '';
        $wholeHours = floor($absHours);
        $minutes = round(($absHours - $wholeHours) * 60);

        if ($minutes > 0) {
            return "{$sign}{$wholeHours}h {$minutes}m";
        }

        return "{$sign}{$wholeHours}h";
    }

    /**
     * Header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportToCsv'),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->resetTable()),
        ];
    }

    /**
     * Export to CSV
     */
    public function exportToCsv()
    {
        $filename = 'project-labor-report-' . Carbon::now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Project',
                'Customer',
                'Actual Hours',
                'Allocated Hours',
                'Variance',
                '% Complete',
                'Labor Cost',
                'Employees'
            ]);

            $projects = $this->getProjectsQuery()->get();

            foreach ($projects as $project) {
                $actual = $this->getProjectHours($project->id);
                $allocated = $project->allocated_hours ?? 0;
                $variance = $allocated > 0 ? $actual - $allocated : 0;
                $progress = $allocated > 0 ? ($actual / $allocated) * 100 : 0;

                fputcsv($file, [
                    $project->name,
                    $project->partner?->name ?? '-',
                    round($actual, 2),
                    round($allocated, 2),
                    round($variance, 2),
                    round($progress, 1) . '%',
                    round($actual * self::DEFAULT_HOURLY_RATE, 2),
                    $this->getEmployeeCount($project->id),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
