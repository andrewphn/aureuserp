<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;
use Illuminate\Support\Facades\Log;

class HierarchyBuilderModal extends Component
{
    // Modal state
    public bool $showModal = false;

    // Annotation data
    public array $annotation = [];
    public array $missingLevels = [];

    // Project context
    public ?int $projectId = null;
    public ?int $roomId = null;
    public ?int $locationId = null;
    public ?int $cabinetRunId = null;

    // Form state for each missing level
    public array $levelForms = [];

    /**
     * Listen for event to open hierarchy builder
     */
    #[On('open-hierarchy-builder')]
    public function openHierarchyBuilder(array $annotation, array $missingLevels, array $context): void
    {
        Log::info('🏗️ Opening Hierarchy Builder', [
            'annotation' => $annotation,
            'missing' => $missingLevels,
            'context' => $context
        ]);

        $this->annotation = $annotation;
        $this->missingLevels = $missingLevels;

        // Set context
        $this->projectId = $context['projectId'] ?? null;
        $this->roomId = $context['roomId'] ?? null;
        $this->locationId = $context['locationId'] ?? null;
        $this->cabinetRunId = $context['cabinetRunId'] ?? null;

        // Initialize form for each missing level
        $this->initializeLevelForms();

        $this->showModal = true;
    }

    /**
     * Initialize form state for each missing level
     */
    protected function initializeLevelForms(): void
    {
        $this->levelForms = [];

        foreach ($this->missingLevels as $level) {
            $entityType = $level['type'];

            $this->levelForms[$entityType] = [
                'mode' => 'create', // 'create' or 'link'
                'create_data' => $this->getDefaultsForLevel($entityType),
                'existing_id' => null,
                'existing_options' => $this->getExistingOptionsForLevel($entityType),
            ];
        }

        Log::info('📝 Initialized level forms', ['forms' => $this->levelForms]);
    }

    /**
     * Get smart defaults for creating an entity
     */
    protected function getDefaultsForLevel(string $entityType): array
    {
        $defaults = [
            'name' => $this->annotation['label'] ?? 'Untitled',
        ];

        switch ($entityType) {
            case 'room':
                $defaults['room_type'] = 'general';
                $defaults['floor_number'] = 1;
                $defaults['project_id'] = $this->projectId;
                break;

            case 'room_location':
                $defaults['location_type'] = 'wall';
                $defaults['room_id'] = $this->roomId;
                break;

            case 'cabinet_run':
                // CabinetRun only has room_location_id (room accessed via roomLocation->room)
                $defaults['room_location_id'] = $this->locationId;
                // Infer run type from view type
                $viewType = $this->annotation['viewType'] ?? 'plan';
                if ($viewType === 'elevation') {
                    $defaults['run_type'] = 'wall';
                    $defaults['name'] = 'Wall Cabinet';
                } else {
                    $defaults['run_type'] = 'base';
                    $defaults['name'] = 'Base Cabinet';
                }
                $defaults['sort_order'] = 0;
                break;

            case 'cabinet':
                $defaults['room_id'] = $this->roomId;
                $defaults['cabinet_run_id'] = $this->cabinetRunId;
                $defaults['product_variant_id'] = 1;
                $defaults['position_in_run'] = 0;
                $defaults['length_inches'] = 24;
                $defaults['depth_inches'] = 24;
                $defaults['height_inches'] = 30;
                $defaults['quantity'] = 1;
                break;
        }

        return $defaults;
    }

    /**
     * Get existing entities that can be linked
     */
    protected function getExistingOptionsForLevel(string $entityType): array
    {
        $options = [];

        try {
            switch ($entityType) {
                case 'room':
                    if ($this->projectId) {
                        $options = Room::where('project_id', $this->projectId)
                            ->get(['id', 'name'])
                            ->map(fn($r) => ['value' => $r->id, 'label' => $r->name])
                            ->toArray();
                    }
                    break;

                case 'room_location':
                    if ($this->roomId) {
                        $options = RoomLocation::where('room_id', $this->roomId)
                            ->get(['id', 'name'])
                            ->map(fn($l) => ['value' => $l->id, 'label' => $l->name])
                            ->toArray();
                    }
                    break;

                case 'cabinet_run':
                    if ($this->locationId) {
                        $options = CabinetRun::where('room_location_id', $this->locationId)
                            ->get(['id', 'name'])
                            ->map(fn($r) => ['value' => $r->id, 'label' => $r->name])
                            ->toArray();
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Failed to load existing options', [
                'type' => $entityType,
                'error' => $e->getMessage()
            ]);
        }

        return $options;
    }

    /**
     * Save the hierarchy and create/link all entities
     */
    public function saveHierarchy(): void
    {
        Log::info('💾 Saving hierarchy', ['forms' => $this->levelForms]);

        $createdIds = [];

        try {
            // Process each level in order (room → location → cabinet_run)
            foreach ($this->missingLevels as $level) {
                $entityType = $level['type'];
                $form = $this->levelForms[$entityType];

                if ($form['mode'] === 'create') {
                    // Create new entity
                    $id = $this->createEntity($entityType, $form['create_data'], $createdIds);
                    $createdIds[$entityType] = $id;
                } else {
                    // Link existing
                    $createdIds[$entityType] = $form['existing_id'];
                }

                // Update context for next level
                $this->updateContextFromCreatedIds($createdIds);
            }

            Log::info('✅ Hierarchy created successfully', ['ids' => $createdIds]);

            // Dispatch event to save annotation with complete hierarchy
            $this->dispatch('hierarchy-completed', [
                'annotation' => $this->annotation,
                'createdIds' => $createdIds,
                'context' => [
                    'roomId' => $this->roomId,
                    'locationId' => $this->locationId,
                    'cabinetRunId' => $this->cabinetRunId,
                ]
            ]);

            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('Failed to save hierarchy', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Failed to create hierarchy: ' . $e->getMessage());
        }
    }

    /**
     * Create entity based on type
     */
    protected function createEntity(string $entityType, array $data, array $createdIds): int
    {
        Log::info("Creating $entityType", ['data' => $data]);

        switch ($entityType) {
            case 'room':
                $room = Room::create($data);
                return $room->id;

            case 'room_location':
                $location = RoomLocation::create($data);
                return $location->id;

            case 'cabinet_run':
                $run = CabinetRun::create($data);
                return $run->id;

            case 'cabinet':
                $cabinet = CabinetSpecification::create($data);
                return $cabinet->id;

            default:
                throw new \Exception("Unknown entity type: $entityType");
        }
    }

    /**
     * Update context IDs as we create entities
     */
    protected function updateContextFromCreatedIds(array $createdIds): void
    {
        if (isset($createdIds['room'])) {
            $this->roomId = $createdIds['room'];
        }
        if (isset($createdIds['room_location'])) {
            $this->locationId = $createdIds['room_location'];
        }
        if (isset($createdIds['cabinet_run'])) {
            $this->cabinetRunId = $createdIds['cabinet_run'];
        }
    }

    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['annotation', 'missingLevels', 'levelForms']);
    }

    /**
     * Get display name for entity type
     */
    public function getEntityDisplayName(string $entityType): string
    {
        return match ($entityType) {
            'room' => 'Room',
            'room_location' => 'Room Location',
            'cabinet_run' => 'Cabinet Run',
            'cabinet' => 'Cabinet',
            default => $entityType
        };
    }

    public function render()
    {
        return view('webkul-project::livewire.hierarchy-builder-modal');
    }
}
