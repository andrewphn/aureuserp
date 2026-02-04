<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Webkul\Project\Models\MilestoneTemplate;

class MilestoneTemplateManager extends Component
{
    public $stages = [
        'discovery' => 'Discovery',
        'design' => 'Design',
        'sourcing' => 'Sourcing',
        'production' => 'Production',
        'delivery' => 'Delivery',
        'general' => 'General',
    ];

    public $stageColors = [
        'discovery' => 'purple',
        'design' => 'blue',
        'sourcing' => 'yellow',
        'production' => 'green',
        'delivery' => 'indigo',
        'general' => 'gray',
    ];

    protected $listeners = ['refreshTemplates' => '$refresh'];

    public function getTemplatesByStageProperty()
    {
        $templates = MilestoneTemplate::orderBy('sort_order')->get();

        $grouped = [];
        foreach ($this->stages as $key => $label) {
            $grouped[$key] = $templates->where('production_stage', $key)->values();
        }

        return $grouped;
    }

    public function updateTemplateOrder($orderedIds, $stage)
    {
        foreach ($orderedIds as $index => $id) {
            MilestoneTemplate::where('id', $id)->update([
                'production_stage' => $stage,
                'sort_order' => $index + 1,
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Template order updated',
        ]);
    }

    public function moveTemplate($templateId, $newStage, $newIndex)
    {
        $template = MilestoneTemplate::find($templateId);
        if (!$template) return;

        // Update the template's stage
        $template->update([
            'production_stage' => $newStage,
            'sort_order' => $newIndex + 1,
        ]);

        // Reorder other templates in the new stage
        $templates = MilestoneTemplate::where('production_stage', $newStage)
            ->where('id', '!=', $templateId)
            ->orderBy('sort_order')
            ->get();

        $order = 1;
        foreach ($templates as $t) {
            if ($order == $newIndex + 1) {
                $order++; // Skip the position we just inserted
            }
            $t->update(['sort_order' => $order]);
            $order++;
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Moved to {$this->stages[$newStage]}",
        ]);
    }

    public function render()
    {
        return view('webkul-project::livewire.milestone-template-manager', [
            'templatesByStage' => $this->templatesByStage,
        ]);
    }
}
