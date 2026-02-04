<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Task;
use Webkul\Project\Services\Gates\GateEvaluator;

/**
 * Project Stage Widget - Shows production stage with gate checklist and tasks drawer
 *
 * Connected to the actual Gate system in the database.
 * Includes expandable task list grouped by milestone.
 */
class ProjectStageWidget extends Widget
{
    public ?Model $record = null;

    protected static bool $isLazy = false;

    protected string $view = 'webkul-project::filament.widgets.project-stage';

    protected int | string | array $columnSpan = 2;

    public bool $showTasks = false;
    public ?int $selectedMilestoneId = null;

    protected array $stageLabels = [
        'discovery' => 'Discovery',
        'design' => 'Design',
        'sourcing' => 'Sourcing',
        'production' => 'Production',
        'delivery' => 'Delivery',
    ];

    protected array $stageIcons = [
        'discovery' => 'heroicon-o-magnifying-glass',
        'design' => 'heroicon-o-pencil-square',
        'sourcing' => 'heroicon-o-shopping-cart',
        'production' => 'heroicon-o-wrench-screwdriver',
        'delivery' => 'heroicon-o-truck',
    ];

    public function getStageData(): array
    {
        if (!$this->record) {
            return [
                'stage' => 'discovery',
                'label' => 'Discovery',
                'icon' => 'heroicon-o-magnifying-glass',
                'gates' => [],
                'progress' => 0,
                'completed' => 0,
                'total' => 0,
            ];
        }

        $currentStage = $this->record->current_production_stage ?? 'discovery';
        $label = $this->stageLabels[$currentStage] ?? ucfirst($currentStage);
        $icon = $this->stageIcons[$currentStage] ?? 'heroicon-o-flag';

        // Get gates from the database via GateEvaluator
        $gates = $this->getGatesFromDatabase();

        // If no gates in database, fall back to hardcoded checks
        if (empty($gates)) {
            $gates = $this->getFallbackGates($currentStage);
        }

        $completed = collect($gates)->where('completed', true)->count();
        $total = count($gates);
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'stage' => $currentStage,
            'label' => $label,
            'icon' => $icon,
            'gates' => $gates,
            'progress' => $progress,
            'completed' => $completed,
            'total' => $total,
        ];
    }

    /**
     * Get gates from the database using GateEvaluator service
     * Shows individual requirements as checklist items
     */
    protected function getGatesFromDatabase(): array
    {
        try {
            $evaluator = app(GateEvaluator::class);
            $gateStatuses = $evaluator->getGateStatus($this->record);

            if (empty($gateStatuses)) {
                return [];
            }

            $items = [];
            foreach ($gateStatuses as $status) {
                // Get the gate's requirements to show individual checklist items
                $gate = \Webkul\Project\Models\Gate::where('gate_key', $status['gate_key'])->first();

                if ($gate && $gate->requirements->count() > 0) {
                    // Show each requirement as a checklist item
                    foreach ($gate->requirements as $requirement) {
                        // Check if this specific requirement passed
                        $requirementPassed = $this->checkRequirementPassed($status, $requirement->id);

                        // Use a friendly label - invert the error message to positive
                        $label = $this->getPositiveLabel($requirement);

                        $items[] = [
                            'label' => $label,
                            'completed' => $requirementPassed,
                            'is_requirement' => true,
                            'help_text' => $requirement->help_text,
                        ];
                    }
                } else {
                    // Fallback to gate name if no requirements
                    $items[] = [
                        'label' => $status['name'],
                        'completed' => $status['passed'],
                        'is_gate' => true,
                    ];
                }
            }

            return $items;
        } catch (\Exception $e) {
            // If gate evaluation fails, return empty to use fallback
            return [];
        }
    }

    /**
     * Check if a specific requirement passed based on gate status
     */
    protected function checkRequirementPassed(array $gateStatus, int $requirementId): bool
    {
        // If gate passed, all requirements passed
        if ($gateStatus['passed']) {
            return true;
        }

        // Check if this requirement is in the blockers list
        if (!empty($gateStatus['blockers'])) {
            foreach ($gateStatus['blockers'] as $blocker) {
                if (($blocker['requirement_id'] ?? null) === $requirementId) {
                    return false;
                }
            }
        }

        // If not in blockers, it passed
        return true;
    }

    /**
     * Convert error message to positive checklist label
     */
    protected function getPositiveLabel(\Webkul\Project\Models\GateRequirement $requirement): string
    {
        // Map of error messages to positive labels
        $positiveLabels = [
            'No client assigned to project' => 'Client assigned',
            'No sales order linked to project' => 'Sales order linked',
            'Deposit payment not received' => 'Deposit received',
            'No rooms/specifications defined' => 'Rooms defined',
            'Not all cabinets have dimensions' => 'Cabinet dimensions set',
            'BOM not generated' => 'BOM generated',
            'Design not approved by customer' => 'Design approved',
            'Final redline changes not confirmed' => 'Redlines confirmed',
            'Not all materials sourced' => 'Materials sourced',
            'Outstanding POs not confirmed' => 'POs confirmed',
            'Not all materials received' => 'Materials received',
            'Materials not staged for production' => 'Materials staged',
            'Not all production tasks completed' => 'Production complete',
            'Not all cabinets have passed QC' => 'QC passed',
            'Blocking defects remain open' => 'Defects resolved',
            'Delivery date not scheduled' => 'Delivery scheduled',
            'Delivery not confirmed' => 'Delivered',
            'Closeout package not delivered' => 'Closeout sent',
            'Customer signoff not received' => 'Customer signoff',
            'Final payment not received' => 'Final payment',
        ];

        return $positiveLabels[$requirement->error_message] ?? $requirement->error_message;
    }

    /**
     * Fallback gates when no database gates are configured
     */
    protected function getFallbackGates(string $stage): array
    {
        $gates = [];

        switch (strtolower($stage)) {
            case 'discovery':
                $salesOrder = $this->record->orders()->first();
                $gates[] = [
                    'label' => 'Sales order created',
                    'completed' => $salesOrder !== null,
                ];
                $gates[] = [
                    'label' => 'Proposal accepted',
                    'completed' => $salesOrder && $salesOrder->proposal_accepted_at !== null,
                ];
                $gates[] = [
                    'label' => 'Deposit received',
                    'completed' => $salesOrder && $salesOrder->deposit_paid_at !== null,
                ];
                break;

            case 'design':
                $gates[] = [
                    'label' => 'Design approved',
                    'completed' => $this->record->design_approved_at !== null,
                ];
                $gates[] = [
                    'label' => 'Redlines confirmed',
                    'completed' => $this->record->redline_approved_at !== null,
                ];
                break;

            case 'sourcing':
                $gates[] = [
                    'label' => 'Materials ordered',
                    'completed' => $this->record->materials_ordered_at !== null,
                ];
                $gates[] = [
                    'label' => 'Materials received',
                    'completed' => $this->record->all_materials_received_at !== null,
                ];
                $gates[] = [
                    'label' => 'Materials staged',
                    'completed' => $this->record->materials_staged_at !== null,
                ];
                break;

            case 'production':
                $totalCabinets = $this->record->cabinets()->count();
                $qcPassedCabinets = $this->record->cabinets()->where('qc_passed', true)->count();

                $gates[] = [
                    'label' => 'Cabinets built',
                    'completed' => $totalCabinets > 0,
                ];
                $gates[] = [
                    'label' => "QC passed ({$qcPassedCabinets}/{$totalCabinets})",
                    'completed' => $totalCabinets > 0 && $qcPassedCabinets >= $totalCabinets,
                ];
                break;

            case 'delivery':
                $salesOrder = $this->record->orders()->first();
                $gates[] = [
                    'label' => 'Delivered',
                    'completed' => $this->record->delivered_at !== null,
                ];
                $gates[] = [
                    'label' => 'Closeout sent',
                    'completed' => $this->record->closeout_delivered_at !== null,
                ];
                $gates[] = [
                    'label' => 'Customer signoff',
                    'completed' => $this->record->customer_signoff_at !== null,
                ];
                $gates[] = [
                    'label' => 'Final payment',
                    'completed' => $salesOrder && $salesOrder->final_paid_at !== null,
                ];
                break;
        }

        return $gates;
    }

    /**
     * Toggle the tasks drawer
     */
    public function toggleTasks(): void
    {
        $this->showTasks = !$this->showTasks;
    }

    /**
     * Select a milestone to filter tasks
     */
    public function selectMilestone(?int $milestoneId): void
    {
        $this->selectedMilestoneId = $this->selectedMilestoneId === $milestoneId ? null : $milestoneId;
    }

    /**
     * Get tasks data grouped by milestone for the current stage
     */
    public function getTasksData(): array
    {
        if (!$this->record) {
            return [
                'total' => 0,
                'done' => 0,
                'progress' => 0,
                'milestones' => [],
                'ungrouped' => [],
            ];
        }

        $currentStage = $this->record->current_production_stage ?? 'discovery';

        // Get milestones for current stage (or all if no stage filter)
        $milestonesQuery = $this->record->milestones()
            ->orderBy('sort_order')
            ->orderBy('deadline')
            ->with(['tasks' => function ($query) {
                $query->whereNull('parent_id')
                    ->orderByRaw("CASE
                        WHEN state = 'in_progress' THEN 1
                        WHEN state = 'blocked' THEN 2
                        WHEN state = 'pending' THEN 3
                        ELSE 4
                    END")
                    ->orderBy('priority', 'desc');
            }]);

        // Filter by production_stage if milestones have it
        if ($currentStage) {
            $milestonesQuery->where('production_stage', $currentStage);
        }

        $milestones = $milestonesQuery->get();

        // Get tasks without milestone
        $ungroupedTasks = $this->record->tasks()
            ->whereNull('parent_id')
            ->whereNull('milestone_id')
            ->orderByRaw("CASE
                WHEN state = 'in_progress' THEN 1
                WHEN state = 'blocked' THEN 2
                WHEN state = 'pending' THEN 3
                ELSE 4
            END")
            ->orderBy('priority', 'desc')
            ->limit(10)
            ->get();

        // Calculate totals
        $allTasks = $this->record->tasks()->whereNull('parent_id')->get();
        $total = $allTasks->count();
        $done = $allTasks->where('state', TaskState::DONE)->count();
        $progress = $total > 0 ? round(($done / $total) * 100) : 0;

        // Format milestones with their tasks
        $formattedMilestones = $milestones->map(function ($milestone) {
            $tasks = $milestone->tasks;
            $milestoneDone = $tasks->where('state', TaskState::DONE)->count();
            $milestoneTotal = $tasks->count();

            return [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'is_completed' => $milestone->is_completed,
                'is_critical' => $milestone->is_critical,
                'deadline' => $milestone->deadline,
                'done' => $milestoneDone,
                'total' => $milestoneTotal,
                'progress' => $milestoneTotal > 0 ? round(($milestoneDone / $milestoneTotal) * 100) : 0,
                'tasks' => $tasks->take(5)->map(fn ($task) => $this->formatTask($task))->toArray(),
            ];
        })->toArray();

        return [
            'total' => $total,
            'done' => $done,
            'progress' => $progress,
            'milestones' => $formattedMilestones,
            'ungrouped' => $ungroupedTasks->map(fn ($task) => $this->formatTask($task))->toArray(),
        ];
    }

    /**
     * Format a task for display
     */
    protected function formatTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'state' => $task->state,
            'state_label' => $task->state?->getLabel() ?? 'Unknown',
            'state_color' => $task->state?->getColor() ?? 'gray',
            'priority' => $task->priority,
            'deadline' => $task->deadline,
        ];
    }

    /**
     * Mark a task as done
     */
    public function markTaskDone(int $taskId): void
    {
        $task = Task::find($taskId);

        if ($task && $task->project_id === $this->record?->id) {
            $task->update(['state' => TaskState::DONE]);
            $this->dispatch('task-updated');
        }
    }

    /**
     * Mark a task as in progress
     */
    public function markTaskInProgress(int $taskId): void
    {
        $task = Task::find($taskId);

        if ($task && $task->project_id === $this->record?->id) {
            $task->update(['state' => TaskState::IN_PROGRESS]);
            $this->dispatch('task-updated');
        }
    }

    #[On('task-updated')]
    public function refreshWidget(): void
    {
        // Livewire will automatically re-render
    }
}
