<?php

namespace App\Livewire;

use Livewire\Component;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Tag;
use Illuminate\Support\Facades\DB;

class ProjectTagsLoader extends Component
{
    public $projectId;
    public $selectedTags = [];
    public $searchQuery = '';
    public $expandedSections = [];
    public $saving = false;

    protected $listeners = ['refreshTags' => '$refresh'];

    public function mount()
    {
        if ($this->projectId) {
            $project = Project::find($this->projectId);
            if ($project) {
                $this->selectedTags = $project->tags()->pluck('tag_id')->toArray();
            }
        }

        // Set commonly-used categories to be expanded by default
        $this->expandedSections = ['common' => true];
    }

    public function toggleTag($tagId)
    {
        if (in_array($tagId, $this->selectedTags)) {
            $this->selectedTags = array_diff($this->selectedTags, [$tagId]);
        } else {
            $this->selectedTags[] = $tagId;
        }
    }

    public function toggleSection($section)
    {
        if (isset($this->expandedSections[$section]) && $this->expandedSections[$section]) {
            $this->expandedSections[$section] = false;
        } else {
            $this->expandedSections[$section] = true;
        }
    }

    public function saveTags()
    {
        if (!$this->projectId) {
            return;
        }

        $this->saving = true;

        try {
            $project = Project::findOrFail($this->projectId);
            $project->tags()->sync($this->selectedTags);

            $this->dispatch('tags-saved', [
                'message' => 'Tags updated successfully',
                'count' => count($this->selectedTags)
            ]);
        } catch (\Exception $e) {
            $this->dispatch('tags-error', [
                'message' => 'Failed to save tags: ' . $e->getMessage()
            ]);
        }

        $this->saving = false;
    }

    public function getFilteredTagsProperty()
    {
        $query = Tag::query();

        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%');
        }

        return $query->orderBy('type')->orderBy('name')->get()->groupBy('type');
    }

    public function render()
    {
        $allTags = $this->filteredTags;

        return view('livewire.project-tags-loader', [
            'allTags' => $allTags,
        ]);
    }
}
