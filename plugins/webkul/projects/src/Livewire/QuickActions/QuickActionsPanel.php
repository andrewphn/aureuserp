<?php

namespace Webkul\Project\Livewire\QuickActions;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Security\Models\User;

class QuickActionsPanel extends Component implements HasForms
{
    use InteractsWithForms;

    public Project $project;

    // Modal states
    public bool $showTaskModal = false;
    public bool $showMilestoneModal = false;
    public bool $showAssignDesignerModal = false;
    public bool $showAssignPurchasingModal = false;

    // Task form data
    public ?string $taskTitle = null;
    public ?string $taskDescription = null;
    public ?int $taskStageId = null;
    public bool $taskPriority = false;
    public ?string $taskDeadline = null;

    // Milestone form data
    public ?string $milestoneName = null;
    public ?string $milestoneDeadline = null;
    public bool $milestoneCritical = false;

    // Assignment form data
    public ?int $selectedDesignerId = null;
    public ?int $selectedPurchasingManagerId = null;

    protected $listeners = ['refreshPanel' => '$refresh'];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->taskStageId = TaskStage::first()?->id;
    }

    // Task Actions
    public function openTaskModal(): void
    {
        $this->resetTaskForm();
        $this->showTaskModal = true;
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
        $this->resetTaskForm();
    }

    protected function resetTaskForm(): void
    {
        $this->taskTitle = null;
        $this->taskDescription = null;
        $this->taskStageId = TaskStage::first()?->id;
        $this->taskPriority = false;
        $this->taskDeadline = null;
    }

    public function createTask(): void
    {
        $this->validate([
            'taskTitle' => 'required|string|max:255',
            'taskDescription' => 'nullable|string',
            'taskStageId' => 'required|exists:projects_task_stages,id',
        ]);

        Task::create([
            'title' => $this->taskTitle,
            'description' => $this->taskDescription,
            'project_id' => $this->project->id,
            'stage_id' => $this->taskStageId,
            'priority' => $this->taskPriority,
            'deadline' => $this->taskDeadline,
            'creator_id' => auth()->id(),
        ]);

        $this->closeTaskModal();
        $this->project->refresh();

        Notification::make()
            ->success()
            ->title('Task Created')
            ->body("Task '{$this->taskTitle}' has been added to the project.")
            ->send();
    }

    // Milestone Actions
    public function openMilestoneModal(): void
    {
        $this->resetMilestoneForm();
        $this->showMilestoneModal = true;
    }

    public function closeMilestoneModal(): void
    {
        $this->showMilestoneModal = false;
        $this->resetMilestoneForm();
    }

    protected function resetMilestoneForm(): void
    {
        $this->milestoneName = null;
        $this->milestoneDeadline = null;
        $this->milestoneCritical = false;
    }

    public function createMilestone(): void
    {
        $this->validate([
            'milestoneName' => 'required|string|max:255',
            'milestoneDeadline' => 'nullable|date',
        ]);

        Milestone::create([
            'name' => $this->milestoneName,
            'project_id' => $this->project->id,
            'deadline' => $this->milestoneDeadline,
            'is_critical' => $this->milestoneCritical,
        ]);

        $this->closeMilestoneModal();
        $this->project->refresh();

        Notification::make()
            ->success()
            ->title('Milestone Created')
            ->body("Milestone '{$this->milestoneName}' has been added.")
            ->send();
    }

    // Team Assignment Actions
    public function openAssignDesignerModal(): void
    {
        $this->selectedDesignerId = $this->project->designer_id;
        $this->showAssignDesignerModal = true;
    }

    public function closeAssignDesignerModal(): void
    {
        $this->showAssignDesignerModal = false;
        $this->selectedDesignerId = null;
    }

    public function assignDesigner(): void
    {
        $this->project->update(['designer_id' => $this->selectedDesignerId]);
        $this->closeAssignDesignerModal();
        $this->project->refresh();

        $userName = $this->selectedDesignerId ? User::find($this->selectedDesignerId)?->name : 'None';

        Notification::make()
            ->success()
            ->title('Designer Updated')
            ->body("Designer set to: {$userName}")
            ->send();
    }

    public function openAssignPurchasingModal(): void
    {
        $this->selectedPurchasingManagerId = $this->project->purchasing_manager_id;
        $this->showAssignPurchasingModal = true;
    }

    public function closeAssignPurchasingModal(): void
    {
        $this->showAssignPurchasingModal = false;
        $this->selectedPurchasingManagerId = null;
    }

    public function assignPurchasingManager(): void
    {
        $this->project->update(['purchasing_manager_id' => $this->selectedPurchasingManagerId]);
        $this->closeAssignPurchasingModal();
        $this->project->refresh();

        $userName = $this->selectedPurchasingManagerId ? User::find($this->selectedPurchasingManagerId)?->name : 'None';

        Notification::make()
            ->success()
            ->title('Purchasing Manager Updated')
            ->body("Purchasing Manager set to: {$userName}")
            ->send();
    }

    // Stage Workflow Actions
    public function advanceStage(): void
    {
        if (!method_exists($this->project, 'canAdvanceToNextStage') || !$this->project->canAdvanceToNextStage()) {
            Notification::make()
                ->danger()
                ->title('Cannot Advance')
                ->body('Stage requirements not met. Check gates status.')
                ->send();
            return;
        }

        $stages = $this->project->stage()->getModel()::orderBy('sort_order')->get();
        $currentIndex = $stages->search(fn($s) => $s->id === $this->project->stage_id);

        if ($currentIndex !== false && $currentIndex < $stages->count() - 1) {
            $nextStage = $stages[$currentIndex + 1];
            $this->project->update(['stage_id' => $nextStage->id]);
            $this->project->refresh();

            Notification::make()
                ->success()
                ->title('Stage Advanced')
                ->body("Project moved to: {$nextStage->name}")
                ->send();
        }
    }

    // Helper methods
    public function getRecentTasks(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->project->tasks()
            ->with('stage')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function getUpcomingMilestones(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->project->milestones()
            ->where(function ($query) {
                $query->whereNull('deadline')
                    ->orWhere('deadline', '>=', now());
            })
            ->orderBy('deadline')
            ->limit(5)
            ->get();
    }

    public function getTaskCounts(): array
    {
        $tasks = $this->project->tasks;
        return [
            'total' => $tasks->count(),
            'pending' => $tasks->whereNull('completed_at')->count(),
            'overdue' => $tasks->filter(fn($t) => $t->deadline && $t->deadline < now() && !$t->completed_at)->count(),
        ];
    }

    public function getStageProgress(): array
    {
        $stages = $this->project->stage()->getModel()::orderBy('sort_order')->get();
        $currentIndex = $stages->search(fn($s) => $s->id === $this->project->stage_id);

        return [
            'stages' => $stages,
            'currentIndex' => $currentIndex !== false ? $currentIndex : 0,
            'total' => $stages->count(),
        ];
    }

    public function getUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::orderBy('name')->get();
    }

    public function getTaskStages(): \Illuminate\Database\Eloquent\Collection
    {
        return TaskStage::orderBy('sort_order')->get();
    }

    public function render()
    {
        return view('webkul-project::livewire.quick-actions.panel');
    }
}
