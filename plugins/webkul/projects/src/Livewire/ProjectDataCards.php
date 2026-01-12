<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;

class ProjectDataCards extends Component
{
    public Project $project;

    public string $viewMode = 'cards'; // 'cards' or 'table'

    public array $expandedRooms = [];

    public array $expandedLocations = [];

    public function mount(Project $project): void
    {
        $this->project = $project;

        // Load preference from localStorage via Alpine.js
        // Default to cards view
        $this->viewMode = 'cards';
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'cards' ? 'table' : 'cards';
    }

    public function toggleRoom(int $roomId): void
    {
        if (in_array($roomId, $this->expandedRooms)) {
            $this->expandedRooms = array_filter($this->expandedRooms, fn($id) => $id !== $roomId);
        } else {
            $this->expandedRooms[] = $roomId;
        }
    }

    public function toggleLocation(int $locationId): void
    {
        if (in_array($locationId, $this->expandedLocations)) {
            $this->expandedLocations = array_filter($this->expandedLocations, fn($id) => $id !== $locationId);
        } else {
            $this->expandedLocations[] = $locationId;
        }
    }

    public function isRoomExpanded(int $roomId): bool
    {
        return in_array($roomId, $this->expandedRooms);
    }

    public function isLocationExpanded(int $locationId): bool
    {
        return in_array($locationId, $this->expandedLocations);
    }

    /**
     * Update a cabinet run's pricing fields inline
     */
    public function updateCabinetRun(int $runId, string $field, ?string $value): void
    {
        $run = CabinetRun::find($runId);

        if (!$run || !in_array($field, ['cabinet_level', 'material_category', 'finish_option'])) {
            return;
        }

        $run->update([$field => $value]);

        // Also update all cabinets in this run to inherit the new value
        $run->cabinets()->update([$field => $value]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Cabinet run updated successfully',
        ]);
    }

    /**
     * Get rooms with computed stats
     */
    public function getRoomsProperty()
    {
        return $this->project->rooms()
            ->with(['locations.cabinetRuns.cabinets'])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($room) {
                $room->location_count = $room->locations->count();
                $room->cabinet_count = $room->locations->sum(fn($loc) => $loc->cabinetRuns->sum(fn($run) => $run->cabinets->count()));
                $room->total_linear_feet = $room->locations->sum(fn($loc) => $loc->cabinetRuns->sum(fn($run) => $run->cabinets->sum('linear_feet')));
                $room->total_value = $room->locations->sum(fn($loc) => $loc->cabinetRuns->sum(fn($run) => $run->cabinets->sum('total_price')));

                return $room;
            });
    }

    /**
     * Get pricing options for dropdowns
     */
    public function getLevelOptionsProperty(): array
    {
        return [
            '' => 'Select an option',
            'Level 1 - Basic ($138/LF)' => 'Level 1 - Basic ($138/LF)',
            'Level 2 - Standard ($168/LF)' => 'Level 2 - Standard ($168/LF)',
            'Level 3 - Enhanced ($192/LF)' => 'Level 3 - Enhanced ($192/LF)',
            'Level 4 - Premium ($210/LF)' => 'Level 4 - Premium ($210/LF)',
            'Level 5 - Custom ($225/LF)' => 'Level 5 - Custom ($225/LF)',
        ];
    }

    public function getMaterialOptionsProperty(): array
    {
        return [
            '' => 'Select an option',
            'Paint Grade (Hard Maple/Poplar)' => 'Paint Grade (Hard Maple/Poplar)',
            'Stain Grade (Oak/Maple)' => 'Stain Grade (Oak/Maple)',
            'Premium (Rifted White Oak/Black Walnut)' => 'Premium (Rifted White Oak/Black Walnut)',
            'Custom/Exotic (Price TBD)' => 'Custom/Exotic (Price TBD)',
        ];
    }

    public function getFinishOptionsProperty(): array
    {
        return [
            '' => 'Select an option',
            'Unfinished' => 'Unfinished',
            'Prime Only' => 'Prime Only',
            'Prime + Paint' => 'Prime + Paint',
            'Custom Color' => 'Custom Color',
            'Clear Coat' => 'Clear Coat',
            'Stain + Clear' => 'Stain + Clear',
            'Color Match Stain + Clear' => 'Color Match Stain + Clear',
            'Two-tone' => 'Two-tone',
        ];
    }

    public function render()
    {
        return view('webkul-project::livewire.project-data-cards', [
            'rooms' => $this->rooms,
            'levelOptions' => $this->levelOptions,
            'materialOptions' => $this->materialOptions,
            'finishOptions' => $this->finishOptions,
        ]);
    }
}
