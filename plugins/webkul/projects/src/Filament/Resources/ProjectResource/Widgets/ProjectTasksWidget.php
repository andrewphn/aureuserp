<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Task;

/**
 * Project Tasks Widget - Shows task progress and quick task management
 *
 * Displays overall task progress with a compact list of active tasks.
 */
class ProjectTasksWidget extends Widget
{
    public ?Model $record = null;

    protected static bool $isLazy = false;

    protected string $view = 'webkul-project::filament.widgets.project-tasks';

    protected int | string | array $columnSpan = 1;

    public function getTasksData(): array
    {
        if (!$this->record) {
            return [
                'total' => 0,
                'done' => 0,
                'in_progress' => 0,
                'pending' => 0,
                'blocked' => 0,
                'progress' => 0,
                'tasks' => [],
            ];
        }

        $tasks = $this->record->tasks()
            ->whereNull('parent_id') // Only top-level tasks
            ->orderByRaw("CASE
                WHEN state = 'in_progress' THEN 1
                WHEN state = 'blocked' THEN 2
                WHEN state = 'pending' THEN 3
                WHEN state = 'change_requested' THEN 4
                ELSE 5
            END")
            ->orderBy('priority', 'desc')
            ->orderBy('deadline', 'asc')
            ->get();

        $total = $tasks->count();
        $done = $tasks->where('state', TaskState::DONE)->count();
        $inProgress = $tasks->where('state', TaskState::IN_PROGRESS)->count();
        $pending = $tasks->where('state', TaskState::PENDING)->count();
        $blocked = $tasks->where('state', TaskState::BLOCKED)->count();

        $progress = $total > 0 ? round(($done / $total) * 100) : 0;

        // Get active tasks (not done, not cancelled)
        $activeTasks = $tasks->filter(function ($task) {
            return !in_array($task->state, [TaskState::DONE, TaskState::CANCELLED]);
        })->take(5)->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'state' => $task->state,
                'state_label' => $task->state?->getLabel() ?? 'Unknown',
                'state_color' => $task->state?->getColor() ?? 'gray',
                'state_icon' => $task->state?->getIcon() ?? 'heroicon-o-question-mark-circle',
                'priority' => $task->priority,
                'deadline' => $task->deadline,
                'assignees' => $task->users->take(2)->map(fn($user) => [
                    'name' => $user->name,
                    'avatar' => $user->avatar_url ?? null,
                ])->toArray(),
            ];
        })->values()->toArray();

        return [
            'total' => $total,
            'done' => $done,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'blocked' => $blocked,
            'progress' => $progress,
            'tasks' => $activeTasks,
        ];
    }

    public function markTaskDone(int $taskId): void
    {
        $task = Task::find($taskId);

        if ($task && $task->project_id === $this->record?->id) {
            $task->update(['state' => TaskState::DONE]);

            $this->dispatch('$refresh');
            $this->dispatch('task-updated');
        }
    }

    public function markTaskInProgress(int $taskId): void
    {
        $task = Task::find($taskId);

        if ($task && $task->project_id === $this->record?->id) {
            $task->update(['state' => TaskState::IN_PROGRESS]);

            $this->dispatch('$refresh');
            $this->dispatch('task-updated');
        }
    }

    #[On('task-updated')]
    public function refreshWidget(): void
    {
        // Livewire will automatically re-render
    }
}
