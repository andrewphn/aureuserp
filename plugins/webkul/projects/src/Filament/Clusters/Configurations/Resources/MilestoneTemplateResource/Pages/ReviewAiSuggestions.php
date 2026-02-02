<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource;
use Webkul\Project\Models\AiTaskSuggestion;
use Webkul\Project\Models\MilestoneTemplateTask;

class ReviewAiSuggestions extends Page
{
    use InteractsWithRecord;

    protected static string $resource = MilestoneTemplateResource::class;

    protected string $view = 'webkul-project::filament.pages.review-ai-suggestions';

    public ?AiTaskSuggestion $suggestion = null;

    public array $tasks = [];

    public ?string $reviewerNotes = null;

    #[Url]
    public ?int $suggestionId = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // Get suggestion from URL parameter or find latest pending
        if ($this->suggestionId) {
            $this->suggestion = AiTaskSuggestion::find($this->suggestionId);
        }

        if (!$this->suggestion) {
            // Get the latest pending suggestion for this template
            $this->suggestion = AiTaskSuggestion::where('milestone_template_id', $this->record->id)
                ->where('status', AiTaskSuggestion::STATUS_PENDING)
                ->latest()
                ->first();
        }

        if (!$this->suggestion) {
            Notification::make()
                ->warning()
                ->title('No Pending Suggestions')
                ->body('There are no pending AI suggestions to review. Generate new suggestions first.')
                ->send();

            $this->redirect(MilestoneTemplateResource::getUrl('edit', ['record' => $this->record]));
            return;
        }

        // Redirect if already reviewed
        if (!$this->suggestion->isPending()) {
            Notification::make()
                ->warning()
                ->title('Already Reviewed')
                ->body('This suggestion has already been reviewed.')
                ->send();

            $this->redirect(MilestoneTemplateResource::getUrl('edit', ['record' => $this->record]));
            return;
        }

        // Initialize tasks array from suggestions
        $this->tasks = collect($this->suggestion->suggested_tasks)->map(function ($task, $index) {
            return [
                'selected' => true,
                'index' => $index,
                'title' => $task['title'] ?? 'Untitled',
                'description' => $task['description'] ?? '',
                'allocated_hours' => $task['allocated_hours'] ?? 0,
                'relative_days' => $task['relative_days'] ?? 0,
                'duration_type' => $task['duration_type'] ?? 'fixed',
                'duration_days' => $task['duration_days'] ?? 1,
                'duration_rate_key' => $task['duration_rate_key'] ?? null,
                'duration_unit_type' => $task['duration_unit_type'] ?? 'linear_feet',
                'duration_min_days' => $task['duration_min_days'] ?? null,
                'duration_max_days' => $task['duration_max_days'] ?? null,
                'priority' => $task['priority'] ?? false,
                'subtasks' => collect($task['subtasks'] ?? [])->map(function ($subtask) {
                    return [
                        'title' => $subtask['title'] ?? 'Untitled',
                        'description' => $subtask['description'] ?? '',
                        'allocated_hours' => $subtask['allocated_hours'] ?? 0,
                        'relative_days' => $subtask['relative_days'] ?? 0,
                        'duration_days' => $subtask['duration_days'] ?? 1,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    public function getTitle(): string
    {
        return 'Review AI Suggestions';
    }

    public function getSubheading(): ?string
    {
        return "Milestone: {$this->record->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Template')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(MilestoneTemplateResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function toggleTask(int $index): void
    {
        if (isset($this->tasks[$index])) {
            $this->tasks[$index]['selected'] = !$this->tasks[$index]['selected'];
        }
    }

    public function approveSelected(): void
    {
        $selectedIndexes = collect($this->tasks)
            ->filter(fn ($task) => $task['selected'] ?? false)
            ->pluck('index')
            ->toArray();

        if (empty($selectedIndexes)) {
            Notification::make()
                ->warning()
                ->title('No Tasks Selected')
                ->body('Please select at least one task to approve.')
                ->send();
            return;
        }

        // Build corrections from modified tasks
        $corrections = $this->buildCorrections();

        // Determine status based on selection
        $totalTasks = count($this->tasks);
        $selectedCount = count($selectedIndexes);
        $isPartial = $selectedCount < $totalTasks;

        if ($isPartial) {
            $createdTasks = $this->suggestion->partialApprove(
                Auth::id(),
                $selectedIndexes,
                $corrections,
                $this->reviewerNotes
            );
        } else {
            $this->suggestion->approve(Auth::id(), $corrections, $this->reviewerNotes);
            $createdTasks = $this->suggestion->applyToTemplate();
        }

        Notification::make()
            ->success()
            ->title('Tasks Created')
            ->body("Created {$createdTasks->count()} task templates from AI suggestions.")
            ->send();

        $this->redirect(MilestoneTemplateResource::getUrl('edit', ['record' => $this->record]));
    }

    public function approveAll(): void
    {
        // Select all tasks
        $this->tasks = collect($this->tasks)->map(function ($task) {
            $task['selected'] = true;
            return $task;
        })->toArray();

        $this->approveSelected();
    }

    public function reject(): void
    {
        $this->suggestion->reject(Auth::id(), $this->reviewerNotes);

        Notification::make()
            ->info()
            ->title('Suggestions Rejected')
            ->body('The AI suggestions have been rejected.')
            ->send();

        $this->redirect(MilestoneTemplateResource::getUrl('edit', ['record' => $this->record]));
    }

    /**
     * Build corrections array from modified form data.
     */
    protected function buildCorrections(): ?array
    {
        $original = $this->suggestion->suggested_tasks;
        $corrections = [];

        foreach ($this->tasks as $task) {
            $index = $task['index'];
            $originalTask = $original[$index] ?? [];
            $taskCorrections = [];

            // Check each field for changes
            $fieldsToCheck = [
                'title', 'description', 'allocated_hours', 'relative_days',
                'duration_type', 'duration_days', 'duration_rate_key',
                'duration_unit_type', 'duration_min_days', 'duration_max_days',
                'priority'
            ];

            foreach ($fieldsToCheck as $field) {
                $currentValue = $task[$field] ?? null;
                $originalValue = $originalTask[$field] ?? null;

                if ($currentValue != $originalValue) {
                    $taskCorrections[$field] = $currentValue;
                }
            }

            // Check subtasks for changes
            if (($task['subtasks'] ?? []) != ($originalTask['subtasks'] ?? [])) {
                $taskCorrections['subtasks'] = $task['subtasks'] ?? [];
            }

            if (!empty($taskCorrections)) {
                $corrections[$index] = $taskCorrections;
            }
        }

        return empty($corrections) ? null : $corrections;
    }

    /**
     * Get the rate key options for the select
     */
    public function getRateKeyOptions(): array
    {
        return MilestoneTemplateTask::COMPANY_RATE_KEYS;
    }
}
