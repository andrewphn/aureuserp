<?php

namespace Webkul\Project\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;

class KanbanQueryService
{
    /**
     * Get statuses collection for kanban board
     */
    public function getStatuses(string $viewMode, ?int $projectFilter = null): Collection
    {
        if ($viewMode === 'tasks') {
            return $this->getTaskStatuses($projectFilter);
        }

        return $this->getProjectStatuses();
    }

    /**
     * Get project stages as statuses (excludes "To Do" - shown in Inbox)
     */
    public function getProjectStatuses(): Collection
    {
        return ProjectStage::query()
            ->where('is_active', true)
            ->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(name)'), ['to do']) // To Do shown in Inbox
            ->orderBy('sort')
            ->get()
            ->map(fn(ProjectStage $stage) => [
                'id' => $stage->id,
                'title' => $stage->name,
                'color' => $stage->color ?? '#6b7280',
                'wip_limit' => $stage->wip_limit,
                'is_collapsed' => $stage->is_collapsed ?? false,
                'max_days_in_stage' => $stage->max_days_in_stage,
                'expiry_warning_days' => $stage->expiry_warning_days ?? 3,
                'notice_message' => $stage->notice_message,
                'notice_severity' => $stage->notice_severity ?? 'info',
            ]);
    }

    /**
     * Get task stages as statuses
     */
    public function getTaskStatuses(?int $projectFilter = null): Collection
    {
        return TaskStage::query()
            ->where('is_active', true)
            ->when($projectFilter, fn($q) => $q->where('project_id', $projectFilter))
            ->orderBy('sort')
            ->get()
            ->map(fn(TaskStage $stage) => [
                'id' => $stage->id,
                'title' => $stage->name,
                'color' => '#6b7280',
                'wip_limit' => null,
                'is_collapsed' => $stage->is_collapsed ?? false,
            ]);
    }

    /**
     * Build projects query with filters
     */
    public function getProjectsQuery(array $filters = []): Builder
    {
        return Project::query()
            ->with(['partner', 'stage', 'milestones', 'orders', 'tasks', 'user'])
            ->when($filters['customer'] ?? null, fn($q, $id) => $q->where('partner_id', $id))
            ->when($filters['person'] ?? null, fn($q, $id) => $q->where('user_id', $id))
            ->when($filters['overdue_only'] ?? false, fn($q) => $q->where('desired_completion_date', '<', now()))
            ->when(($filters['widget'] ?? 'all') !== 'all', fn($q) => $this->applyWidgetFilter($q, $filters['widget'], 'projects'))
            ->when($filters['sort_by'] ?? null, fn($q, $col) => $q->orderBy($col, $filters['sort_direction'] ?? 'asc'));
    }

    /**
     * Build tasks query with filters
     */
    public function getTasksQuery(array $filters = []): Builder
    {
        return Task::query()
            ->with(['project', 'stage', 'users', 'subTasks', 'creator'])
            ->when($filters['project'] ?? null, fn($q, $id) => $q->where('project_id', $id))
            ->when($filters['person'] ?? null, fn($q, $id) => $q->whereHas('users', fn($u) => $u->where('users.id', $id)))
            ->when(($filters['widget'] ?? 'all') !== 'all', fn($q) => $this->applyWidgetFilter($q, $filters['widget'], 'tasks'))
            ->when($filters['sort_by'] ?? null, function ($q, $col) use ($filters) {
                $direction = $filters['sort_direction'] ?? 'asc';
                return match ($col) {
                    'desired_completion_date' => $q->orderBy('deadline', $direction),
                    'name' => $q->orderBy('title', $direction),
                    'created_at' => $q->orderBy('created_at', $direction),
                    default => $q->orderBy('sort'),
                };
            });
    }

    /**
     * Apply widget filter to query
     */
    public function applyWidgetFilter(Builder $query, string $filter, string $type = 'projects'): Builder
    {
        if ($type === 'tasks') {
            return $this->applyTaskWidgetFilter($query, $filter);
        }

        return $this->applyProjectWidgetFilter($query, $filter);
    }

    /**
     * Apply widget filter for projects
     */
    protected function applyProjectWidgetFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'blocked' => $query->where(function ($q) {
                $q->whereHas('tasks', fn($t) => $t->where('state', 'blocked'))
                    ->orWhereDoesntHave('orders')
                    ->orWhereNull('partner_id');
            }),
            'overdue' => $query->where('desired_completion_date', '<', now()),
            'due_soon' => $query->whereBetween('desired_completion_date', [now(), now()->addDays(7)]),
            'on_track' => $query->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('desired_completion_date')
                        ->orWhere('desired_completion_date', '>=', now());
                })
                ->whereDoesntHave('tasks', fn($t) => $t->where('state', 'blocked'))
                ->whereHas('orders')
                ->whereNotNull('partner_id');
            }),
            default => $query,
        };
    }

    /**
     * Apply widget filter for tasks
     */
    protected function applyTaskWidgetFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'blocked' => $query->where('state', 'blocked'),
            'overdue' => $query->where('deadline', '<', now()),
            'due_soon' => $query->whereBetween('deadline', [now(), now()->addDays(7)]),
            'in_progress' => $query->where('state', 'in_progress'),
            'done' => $query->where('state', 'done'),
            'cancelled' => $query->where('state', 'cancelled'),
            default => $query,
        };
    }

    /**
     * Get filter counts for projects
     */
    public function getProjectFilterCounts(): array
    {
        $blockedQuery = Project::where(function($q) {
            $q->whereHas('tasks', fn($t) => $t->where('state', 'blocked'))
              ->orWhereDoesntHave('orders')
              ->orWhereNull('partner_id');
        });

        $overdueQuery = Project::where('desired_completion_date', '<', now());

        $dueSoonQuery = Project::whereBetween('desired_completion_date', [now(), now()->addDays(7)]);

        $onTrackQuery = Project::where(function($q) {
            $q->whereNull('desired_completion_date')
              ->orWhere('desired_completion_date', '>=', now());
        })
        ->whereDoesntHave('tasks', fn($t) => $t->where('state', 'blocked'))
        ->whereHas('orders')
        ->whereNotNull('partner_id');

        return [
            'total' => Project::count(),
            'blocked' => (clone $blockedQuery)->count(),
            'overdue' => (clone $overdueQuery)->count(),
            'due_soon' => (clone $dueSoonQuery)->count(),
            'on_track' => (clone $onTrackQuery)->count(),
            'total_lf' => Project::sum('estimated_linear_feet') ?? 0,
            'blocked_lf' => (clone $blockedQuery)->sum('estimated_linear_feet') ?? 0,
            'overdue_lf' => (clone $overdueQuery)->sum('estimated_linear_feet') ?? 0,
            'due_soon_lf' => (clone $dueSoonQuery)->sum('estimated_linear_feet') ?? 0,
            'on_track_lf' => (clone $onTrackQuery)->sum('estimated_linear_feet') ?? 0,
        ];
    }

    /**
     * Get filter counts for tasks
     */
    public function getTaskFilterCounts(?int $projectFilter = null): array
    {
        $baseQuery = Task::query()
            ->when($projectFilter, fn($q) => $q->where('project_id', $projectFilter));

        return [
            'total' => (clone $baseQuery)->count(),
            'blocked' => (clone $baseQuery)->where('state', 'blocked')->count(),
            'overdue' => (clone $baseQuery)->where('deadline', '<', now())->where('state', '!=', 'done')->count(),
            'due_soon' => (clone $baseQuery)->whereBetween('deadline', [now(), now()->addDays(7)])->where('state', '!=', 'done')->count(),
            'in_progress' => (clone $baseQuery)->where('state', 'in_progress')->count(),
            'done' => (clone $baseQuery)->where('state', 'done')->count(),
            'cancelled' => (clone $baseQuery)->where('state', 'cancelled')->count(),
        ];
    }
}
