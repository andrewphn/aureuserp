<?php

namespace Webkul\Project\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;

/**
 * Project Gantt Chart Livewire Component
 *
 * Displays projects on a Gantt timeline using Frappe Gantt library.
 */
class ProjectGanttChart extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * Gantt tasks data for the chart.
     */
    public array $tasks = [];

    /**
     * Projects data for sidebar display.
     */
    public array $projectsData = [];

    /**
     * Current view mode (Day, Week, Month, Quarter, Year).
     */
    public string $viewMode = 'Month';

    /**
     * Stage filter - show only projects in this stage.
     */
    public ?int $stageFilter = null;

    /**
     * Date range filters.
     */
    public ?string $dateRangeStart = null;
    public ?string $dateRangeEnd = null;

    /**
     * Available project stages for filtering.
     */
    public array $stages = [];

    /**
     * Stage data with colors for legend.
     */
    public array $stageData = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->dateRangeStart = now()->subMonths(1)->format('Y-m-d');
        $this->dateRangeEnd = now()->addMonths(6)->format('Y-m-d');
        $this->loadStages();
        $this->loadProjects();
    }

    /**
     * Load available stages for filtering.
     */
    protected function loadStages(): void
    {
        $stagesQuery = ProjectStage::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        $this->stages = $stagesQuery->pluck('name', 'id')->toArray();

        // Load stage data with colors for dynamic CSS and legend
        $this->stageData = $stagesQuery->map(fn($stage) => [
            'id' => $stage->id,
            'name' => $stage->name,
            'key' => $stage->stage_key ?? 'stage-' . $stage->id,
            'color' => $stage->color ?? '#6b7280',
        ])->toArray();
    }

    /**
     * Load projects and transform to Gantt format.
     */
    public function loadProjects(): void
    {
        $query = Project::query()
            ->with(['stage', 'partner', 'milestones', 'dependsOn', 'orders' => fn($q) => $q->orderBy('id', 'desc')->limit(1)])
            ->whereNotNull('start_date')
            ->where(function ($q) {
                $q->whereNotNull('desired_completion_date')
                  ->orWhereNotNull('end_date');
            })
            ->orderBy('start_date');

        // Apply stage filter
        if ($this->stageFilter) {
            $query->where('stage_id', $this->stageFilter);
        }

        // Apply date range filter
        if ($this->dateRangeStart && $this->dateRangeEnd) {
            $query->where(function ($q) {
                $q->whereBetween('start_date', [$this->dateRangeStart, $this->dateRangeEnd])
                  ->orWhere(function ($q2) {
                      $q2->where('desired_completion_date', '>=', $this->dateRangeStart)
                         ->where('desired_completion_date', '<=', $this->dateRangeEnd);
                  })
                  ->orWhere(function ($q3) {
                      $q3->where('start_date', '<=', $this->dateRangeStart)
                         ->where('desired_completion_date', '>=', $this->dateRangeEnd);
                  });
            });
        }

        $projects = $query->get();

        $this->tasks = [];
        $this->projectsData = [];

        foreach ($projects as $project) {
            // Calculate progress based on milestones
            $totalMilestones = $project->milestones->count();
            $completedMilestones = $project->milestones->where('is_completed', true)->count();
            $progress = $totalMilestones > 0 ? ($completedMilestones / $totalMilestones) * 100 : 0;

            // Get end date (prefer desired_completion_date, fallback to end_date)
            $endDate = $project->desired_completion_date ?? $project->end_date ?? $project->start_date->copy()->addDays(30);

            // Get dependencies as comma-separated IDs
            $dependencies = $project->dependsOn->pluck('id')->map(fn($id) => (string) $id)->implode(',');

            // Calculate days remaining
            $daysRemaining = $endDate ? (int) now()->diffInDays($endDate, false) : null;
            $isOverdue = $daysRemaining !== null && $daysRemaining < 0;

            // Get project value from orders
            $projectValue = $project->orders->first()?->amount_total;

            // Store project data for sidebar
            $this->projectsData[] = [
                'id' => $project->id,
                'name' => $project->name,
                'customer' => $project->partner?->name ?? '',
                'stage' => $project->stage?->name ?? '',
                'stage_color' => $project->stage?->color ?? '#6b7280',
                'start_date' => $project->start_date->format('M j'),
                'end_date' => $endDate->format('M j'),
                'days_remaining' => $daysRemaining,
                'is_overdue' => $isOverdue,
                'linear_feet' => $project->estimated_linear_feet ?? null,
                'value' => $projectValue,
                'progress' => round($progress),
                'milestones_done' => $completedMilestones,
                'milestones_total' => $totalMilestones,
            ];

            $this->tasks[] = [
                'id' => (string) $project->id,
                'name' => $project->name,
                'start' => $project->start_date->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'progress' => round($progress),
                'dependencies' => $dependencies,
                'custom_class' => 'stage-' . ($project->stage?->stage_key ?? 'default'),
                // Additional data for popup
                'customer' => $project->partner?->name ?? '',
                'stage' => $project->stage?->name ?? '',
                'stage_color' => $project->stage?->color ?? 'gray',
                'linear_feet' => $project->estimated_linear_feet ?? '',
            ];

            // Add milestones as separate items (shown as diamond markers)
            foreach ($project->milestones as $milestone) {
                if ($milestone->deadline) {
                    $this->tasks[] = [
                        'id' => 'milestone-' . $milestone->id,
                        'name' => $milestone->name,
                        'start' => $milestone->deadline->format('Y-m-d'),
                        'end' => $milestone->deadline->format('Y-m-d'),
                        'progress' => $milestone->is_completed ? 100 : 0,
                        'dependencies' => (string) $project->id,
                        'custom_class' => 'milestone' . ($milestone->is_critical ? ' critical' : ''),
                    ];
                }
            }
        }
    }

    /**
     * Update project dates when dragged on the Gantt chart.
     */
    #[On('gantt-date-change')]
    public function updateProjectDates(int $projectId, string $start, string $end): void
    {
        $project = Project::find($projectId);

        if (!$project) {
            return;
        }

        $project->update([
            'start_date' => $start,
            'desired_completion_date' => $end,
        ]);

        Notification::make()
            ->title('Dates Updated')
            ->body("{$project->name} dates updated")
            ->success()
            ->send();

        $this->loadProjects();
        $this->dispatch('gantt-tasks-updated', tasks: $this->tasks);
    }

    /**
     * Update project progress.
     */
    #[On('gantt-progress-change')]
    public function updateProjectProgress(int $projectId, int $progress): void
    {
        // Progress is calculated from milestones, so we don't update it directly
        // But we could track it separately if needed
    }

    /**
     * Set the view mode.
     */
    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        $this->dispatch('gantt-view-mode-changed', mode: $mode);
    }

    /**
     * Apply filters when stage filter changes.
     */
    public function updatedStageFilter(): void
    {
        $this->loadProjects();
    }

    /**
     * Apply filters when date range changes.
     */
    public function updatedDateRangeStart(): void
    {
        $this->loadProjects();
    }

    /**
     * Apply filters when date range changes.
     */
    public function updatedDateRangeEnd(): void
    {
        $this->loadProjects();
    }

    /**
     * Handle notification events from frontend.
     */
    #[On('notify')]
    public function notify(string $type, string $title, string $body): void
    {
        $notification = Notification::make()
            ->title($title)
            ->body($body);

        match($type) {
            'success' => $notification->success(),
            'error' => $notification->danger(),
            'warning' => $notification->warning(),
            default => $notification->info(),
        };

        $notification->send();
    }

    /**
     * Handle view mode changes from keyboard shortcuts.
     */
    #[On('set-view-mode')]
    public function handleViewModeChange(string $mode): void
    {
        $this->setViewMode($mode);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('webkul-project::livewire.project-gantt-chart');
    }
}
