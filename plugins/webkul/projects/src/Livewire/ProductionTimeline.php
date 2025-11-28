<?php

namespace Webkul\Project\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Webkul\Project\Models\Project;

/**
 * Production Timeline class
 *
 */
class ProductionTimeline extends Component implements HasForms
{
    use InteractsWithForms;

    public Project $project;
    public array $stages = [];
    public array $milestones = [];
    public int $daysRemaining = 0;
    public int $overdueCount = 0;
    public int $progress = 0;
    public string $expandedStage = '';

    /**
     * Mount
     *
     * @param Project $project
     * @return void
     */
    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->expandedStage = $this->project->current_production_stage ?? 'discovery';
        $this->loadTimelineData();
    }

    /**
     * Load Timeline Data
     *
     * @return void
     */
    protected function loadTimelineData(): void
    {
        // Define production stages
        $stageDefinitions = [
            'discovery' => ['label' => 'Discovery', 'icon' => 'magnifying-glass'],
            'design' => ['label' => 'Design', 'icon' => 'pencil-square'],
            'sourcing' => ['label' => 'Sourcing', 'icon' => 'shopping-cart'],
            'production' => ['label' => 'Production', 'icon' => 'wrench-screwdriver'],
            'delivery' => ['label' => 'Delivery', 'icon' => 'truck'],
        ];

        $currentStage = $this->project->current_production_stage ?? 'discovery';
        $stageKeys = array_keys($stageDefinitions);
        $currentIndex = array_search($currentStage, $stageKeys);

        // Build stages array with status
        foreach ($stageDefinitions as $key => $def) {
            $index = array_search($key, $stageKeys);
            $status = 'upcoming';

            if ($index < $currentIndex) {
                $status = 'completed';
            } elseif ($index === $currentIndex) {
                $status = 'current';
            }

            $this->stages[] = [
                'key' => $key,
                'label' => $def['label'],
                'icon' => $def['icon'],
                'status' => $status,
                'milestones' => $this->getMilestonesForStage($key),
            ];
        }

        // Load project metrics
        $this->daysRemaining = $this->project->days_remaining ?? 0;
        $this->overdueCount = $this->project->overdue_milestones->count();
        $this->progress = $this->project->progress_percentage;
    }

    /**
     * Get Milestones For Stage
     *
     * @param string $stageKey
     * @return array
     */
    protected function getMilestonesForStage(string $stageKey): array
    {
        if (!$this->project->start_date || !$this->project->desired_completion_date) {
            return [];
        }

        $stages = ['discovery', 'design', 'sourcing', 'production', 'delivery'];
        $stageIndex = array_search($stageKey, $stages);

        if ($stageIndex === false) {
            return [];
        }

        // Calculate date range for this stage
        $totalDays = $this->project->start_date->diffInDays($this->project->desired_completion_date);
        $daysPerStage = $totalDays / count($stages);

        $stageStart = $this->project->start_date->copy()->addDays($daysPerStage * $stageIndex);
        $stageEnd = $this->project->start_date->copy()->addDays($daysPerStage * ($stageIndex + 1));

        return $this->project->milestones()
            ->whereBetween('deadline', [$stageStart, $stageEnd])
            ->orderBy('deadline')
            ->get()
            ->map(function ($milestone) {
                return [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'deadline' => $milestone->deadline?->format('M d'),
                    'is_completed' => $milestone->is_completed,
                    'is_overdue' => $milestone->deadline && $milestone->deadline->isPast() && !$milestone->is_completed,
                ];
            })
            ->toArray();
    }

    /**
     * Change Stage
     *
     * @param string $stage
     * @return void
     */
    public function changeStage(string $stage): void
    {
        $this->project->update(['current_production_stage' => $stage]);
        $this->expandedStage = $stage;
        $this->loadTimelineData();
        $this->dispatch('stage-changed', stage: $stage);
    }

    /**
     * Toggle Stage
     *
     * @param string $stage
     * @return void
     */
    public function toggleStage(string $stage): void
    {
        if ($this->expandedStage === $stage) {
            $this->expandedStage = '';
        } else {
            $this->expandedStage = $stage;
        }
    }

    /**
     * Render
     *
     */
    public function render()
    {
        return view('webkul-project::livewire.production-timeline');
    }
}
