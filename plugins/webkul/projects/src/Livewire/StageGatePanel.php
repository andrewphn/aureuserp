<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Webkul\Project\Events\ProjectGateFailed;
use Webkul\Project\Events\ProjectGatePassed;
use Webkul\Project\Events\ProjectStageChanged;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\StageTransition;
use Webkul\Project\Services\Gates\GateEvaluator;
use Webkul\Project\Services\Locks\EntityLockService;

/**
 * Stage Gate Panel Livewire Component
 *
 * Displays the current stage status, gate requirements, and blockers.
 * Allows users to attempt stage advancement when gates pass.
 */
class StageGatePanel extends Component
{
    public Project $project;
    public array $gateStatus = [];
    public bool $showBlockersModal = false;
    public ?string $selectedGateKey = null;
    public array $selectedGateBlockers = [];
    public bool $canAdvance = false;
    public bool $isEvaluating = false;

    protected GateEvaluator $evaluator;

    protected $listeners = [
        'projectUpdated' => 'refreshGateStatus',
        'gateEvaluated' => 'refreshGateStatus',
    ];

    public function boot(GateEvaluator $evaluator): void
    {
        $this->evaluator = $evaluator;
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->refreshGateStatus();
    }

    /**
     * Refresh gate status for all current stage gates.
     */
    public function refreshGateStatus(): void
    {
        $this->isEvaluating = true;
        $this->project->refresh();
        
        $this->gateStatus = $this->evaluator->getGateStatus($this->project);
        $this->canAdvance = $this->evaluator->canAdvance($this->project);
        
        $this->isEvaluating = false;
    }

    /**
     * Attempt to advance to the next stage.
     */
    public function attemptAdvance(bool $force = false): void
    {
        if (!$force && !$this->canAdvance) {
            $this->showBlockers();
            return;
        }

        $currentStage = $this->project->stage;
        $currentGate = $this->project->getCurrentGate();

        // Evaluate gate
        if ($currentGate && !$force) {
            $result = $this->evaluator->evaluate($this->project, $currentGate);
            
            if (!$result->passed) {
                event(new ProjectGateFailed($this->project, $currentGate, $result->evaluation, $result->failureReasons));
                $this->showGateBlockers($currentGate->gate_key);
                return;
            }

            event(new ProjectGatePassed($this->project, $currentGate, $result->evaluation));

            // Apply locks if gate requires it
            if ($currentGate->appliesAnyLock()) {
                app(EntityLockService::class)->applyGateLocks($this->project, $currentGate);
            }
        }

        // Get next stage
        $nextStage = $this->getNextStage();
        if (!$nextStage) {
            session()->flash('message', 'No next stage available.');
            return;
        }

        // Record transition
        StageTransition::record(
            $this->project,
            $currentStage,
            $nextStage,
            $force ? StageTransition::TYPE_FORCE : StageTransition::TYPE_ADVANCE,
            $currentGate
        );

        // Update project stage
        $this->project->update([
            'stage_id' => $nextStage->id,
            'stage_entered_at' => now(),
        ]);

        // Dispatch event
        event(new ProjectStageChanged($this->project, $currentStage, $nextStage));

        // Refresh status
        $this->refreshGateStatus();

        session()->flash('message', "Advanced to {$nextStage->name}");
        $this->dispatch('projectUpdated');
    }

    /**
     * Show the blockers modal for all gates.
     */
    public function showBlockers(): void
    {
        $blockers = $this->evaluator->getBlockers($this->project);
        
        if (!empty($blockers)) {
            $firstBlocker = reset($blockers);
            $this->selectedGateKey = array_key_first($blockers);
            $this->selectedGateBlockers = $firstBlocker['blockers'] ?? [];
            $this->showBlockersModal = true;
        }
    }

    /**
     * Show blockers for a specific gate.
     */
    public function showGateBlockers(string $gateKey): void
    {
        $gate = Gate::findByKey($gateKey);
        if (!$gate) {
            return;
        }

        $result = $this->evaluator->evaluate($this->project, $gate);
        
        $this->selectedGateKey = $gateKey;
        $this->selectedGateBlockers = $result->failureReasons;
        $this->showBlockersModal = true;
    }

    /**
     * Close the blockers modal.
     */
    public function closeBlockersModal(): void
    {
        $this->showBlockersModal = false;
        $this->selectedGateKey = null;
        $this->selectedGateBlockers = [];
    }

    /**
     * Get the next stage in sequence.
     */
    protected function getNextStage()
    {
        $currentSort = $this->project->stage?->sort ?? 0;
        
        return $this->project->stage
            ->where('is_active', true)
            ->where('sort', '>', $currentSort)
            ->orderBy('sort')
            ->first();
    }

    /**
     * Get the lock status for the project.
     */
    public function getLockStatus(): array
    {
        return [
            'design_locked' => $this->project->isDesignLocked(),
            'procurement_locked' => $this->project->isProcurementLocked(),
            'production_locked' => $this->project->isProductionLocked(),
            'design_locked_at' => $this->project->design_locked_at?->format('M j, Y'),
            'procurement_locked_at' => $this->project->procurement_locked_at?->format('M j, Y'),
            'production_locked_at' => $this->project->production_locked_at?->format('M j, Y'),
        ];
    }

    /**
     * Get the overall progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if (empty($this->gateStatus)) {
            return 0;
        }

        $totalRequirements = 0;
        $passedRequirements = 0;

        foreach ($this->gateStatus as $gate) {
            $totalRequirements += $gate['requirements_total'];
            $passedRequirements += $gate['requirements_passed'];
        }

        if ($totalRequirements === 0) {
            return 100;
        }

        return round(($passedRequirements / $totalRequirements) * 100, 1);
    }

    public function render()
    {
        return view('webkul-project::livewire.stage-gate-panel', [
            'currentStage' => $this->project->stage,
            'gates' => $this->gateStatus,
            'lockStatus' => $this->getLockStatus(),
            'progress' => $this->getProgressPercentage(),
        ]);
    }
}
