<?php

namespace Webkul\Project\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Webkul\Project\Models\Project;

class MilestoneTimeline extends Component implements HasForms
{
    use InteractsWithForms;

    public Project $project;
    public array $milestones = [];
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->startDate = $project->start_date?->format('M d, Y');
        $this->endDate = $project->desired_completion_date?->format('M d, Y');

        // Load milestones ordered by deadline
        $this->milestones = $project->milestones()
            ->orderBy('deadline')
            ->get()
            ->map(function ($milestone) {
                return [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'deadline' => $milestone->deadline?->format('M d, Y'),
                    'deadline_time' => $milestone->deadline?->format('h:i A'),
                    'is_completed' => $milestone->is_completed,
                    'completed_at' => $milestone->completed_at?->format('M d, Y'),
                    'is_past_due' => $milestone->deadline && $milestone->deadline->isPast() && !$milestone->is_completed,
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('projects::livewire.milestone-timeline');
    }
}
