<?php

namespace App\Livewire;

use App\Services\HierarchySearchService;
use Livewire\Component;

/**
 * Hierarchy Search Select class
 *
 */
class HierarchySearchSelect extends Component
{
    public $search = '';
    public $selectedPath = '';
    public $showDropdown = false;
    public $selectedIndex = -1;

    // Values to emit back to parent form
    public $projectId = null;
    public $roomId = null;
    public $roomLocationId = null;
    public $cabinetRunId = null;
    public $cabinetSpecificationId = null;

    protected $searchService;

    /**
     * Boot
     *
     * @param HierarchySearchService $searchService
     * @return void
     */
    public function boot(HierarchySearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Mount
     *
     * @param mixed $projectId
     * @param mixed $roomId
     * @param mixed $roomLocationId
     * @param mixed $cabinetRunId
     * @param mixed $cabinetSpecificationId
     */
    public function mount($projectId = null, $roomId = null, $roomLocationId = null, $cabinetRunId = null, $cabinetSpecificationId = null)
    {
        $this->projectId = $projectId;
        $this->roomId = $roomId;
        $this->roomLocationId = $roomLocationId;
        $this->cabinetRunId = $cabinetRunId;
        $this->cabinetSpecificationId = $cabinetSpecificationId;

        // Build initial display path if values are set
        if ($this->roomId || $this->roomLocationId || $this->cabinetRunId || $this->cabinetSpecificationId) {
            $this->updateDisplayPath();
        }
    }

    /**
     * Updated Search
     *
     */
    public function updatedSearch()
    {
        $this->showDropdown = strlen($this->search) >= 2;
        $this->selectedIndex = -1;
    }

    public function getSearchResultsProperty()
    {
        if (strlen($this->search) < 2) {
            return collect([]);
        }

        return $this->searchService->search($this->search, $this->projectId);
    }

    public function getRecentlyUsedProperty()
    {
        return $this->searchService->getRecentlyUsed();
    }

    /**
     * Select Result
     *
     * @param mixed $index
     */
    public function selectResult($index)
    {
        $results = $this->searchResults;

        if (!isset($results[$index])) {
            return;
        }

        $result = $results[$index];
        $this->applySelection($result);
    }

    /**
     * Select Recent
     *
     * @param mixed $index
     */
    public function selectRecent($index)
    {
        $recent = $this->recentlyUsed;

        if (!isset($recent[$index])) {
            return;
        }

        $result = $recent[$index];
        $this->applySelection($result);
    }

    /**
     * Apply Selection
     *
     * @param mixed $result
     */
    protected function applySelection($result)
    {
        // Set all hierarchy values based on selection
        $this->projectId = $result['project_id'];
        $this->roomId = $result['room_id'];
        $this->roomLocationId = $result['room_location_id'];
        $this->cabinetRunId = $result['cabinet_run_id'];
        $this->cabinetSpecificationId = $result['cabinet_specification_id'];

        // Update display
        $this->selectedPath = $result['display_path'];
        $this->search = '';
        $this->showDropdown = false;

        // Add to recently used
        $this->searchService->addToRecentlyUsed($result);

        // Dispatch browser event to update parent form
        $this->dispatch('hierarchySelected',
            project_id: $this->projectId,
            room_id: $this->roomId,
            room_location_id: $this->roomLocationId,
            cabinet_run_id: $this->cabinetRunId,
            cabinet_specification_id: $this->cabinetSpecificationId,
        );
    }

    /**
     * Clear Selection
     *
     */
    public function clearSelection()
    {
        $this->projectId = null;
        $this->roomId = null;
        $this->roomLocationId = null;
        $this->cabinetRunId = null;
        $this->cabinetSpecificationId = null;
        $this->selectedPath = '';
        $this->search = '';
        $this->showDropdown = false;

        $this->dispatch('hierarchyCleared');
    }

    /**
     * Clear Recently Used
     *
     */
    public function clearRecentlyUsed()
    {
        $this->searchService->clearRecentlyUsed();
    }

    /**
     * Close Dropdown
     *
     */
    public function closeDropdown()
    {
        $this->showDropdown = false;
        $this->search = '';
    }

    /**
     * Update Display Path
     *
     */
    protected function updateDisplayPath()
    {
        // Build display path from current IDs
        $parts = [];

        if ($this->projectId) {
            $project = \Webkul\Project\Models\Project::find($this->projectId);
            if ($project) {
                $parts[] = $project->name;
            }
        }

        if ($this->roomId) {
            $room = \Webkul\Project\Models\Room::find($this->roomId);
            if ($room) {
                $parts[] = $room->name;
            }
        }

        if ($this->roomLocationId) {
            $location = \Webkul\Project\Models\RoomLocation::find($this->roomLocationId);
            if ($location) {
                $parts[] = $location->name;
            }
        }

        if ($this->cabinetRunId) {
            $run = \Webkul\Project\Models\CabinetRun::find($this->cabinetRunId);
            if ($run) {
                $parts[] = $run->name;
            }
        }

        if ($this->cabinetSpecificationId) {
            $cabinet = \Webkul\Project\Models\CabinetSpecification::find($this->cabinetSpecificationId);
            if ($cabinet) {
                $parts[] = $cabinet->cabinet_number;
            }
        }

        $this->selectedPath = implode(' → ', $parts);
    }

    /**
     * Render
     *
     */
    public function render()
    {
        return view('livewire.hierarchy-search-select');
    }
}
