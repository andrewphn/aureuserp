<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Pullout;

/**
 * Entity CRUD Modal
 *
 * Handles create/edit/delete for all project entities in the hierarchy:
 * Room → RoomLocation → CabinetRun → Cabinet → CabinetSection → Components
 */
class EntityCrudModal extends Component
{
    // Modal state
    public bool $showModal = false;
    public string $entityType = '';
    public string $mode = 'create'; // create, edit
    public ?int $entityId = null;
    public ?int $parentId = null;

    // Project context
    public int $projectId;

    // Form data
    public array $formData = [];

    // Entity type labels
    protected array $entityLabels = [
        'room' => 'Room',
        'room_location' => 'Room Location',
        'cabinet_run' => 'Cabinet Run',
        'cabinet' => 'Cabinet',
        'section' => 'Cabinet Section',
        'door' => 'Door',
        'drawer' => 'Drawer',
        'shelf' => 'Shelf',
        'pullout' => 'Pullout',
    ];

    // Room types
    protected array $roomTypes = [
        'kitchen' => 'Kitchen',
        'bathroom' => 'Bathroom',
        'laundry' => 'Laundry',
        'pantry' => 'Pantry',
        'closet' => 'Closet',
        'mudroom' => 'Mudroom',
        'office' => 'Office',
        'bedroom' => 'Bedroom',
        'living_room' => 'Living Room',
        'dining_room' => 'Dining Room',
        'garage' => 'Garage',
        'basement' => 'Basement',
        'other' => 'Other',
    ];

    // Location types
    protected array $locationTypes = [
        'wall' => 'Wall',
        'island' => 'Island',
        'peninsula' => 'Peninsula',
        'corner' => 'Corner',
        'alcove' => 'Alcove',
        'sink_wall' => 'Sink Wall',
        'range_wall' => 'Range Wall',
        'refrigerator_wall' => 'Refrigerator Wall',
    ];

    // Cabinet run types
    protected array $runTypes = [
        'base' => 'Base Cabinets',
        'wall' => 'Wall Cabinets',
        'tall' => 'Tall Cabinets',
        'island' => 'Island',
    ];

    // Cabinet types
    protected array $cabinetTypes = [
        'base' => 'Base',
        'wall' => 'Wall',
        'tall' => 'Tall',
        'vanity' => 'Vanity',
        'specialty' => 'Specialty',
    ];

    // Pricing tiers
    protected array $pricingTiers = [
        '1' => 'Level 1 - Basic',
        '2' => 'Level 2 - Standard',
        '3' => 'Level 3 - Enhanced',
        '4' => 'Level 4 - Premium',
        '5' => 'Level 5 - Custom',
    ];

    // Material categories
    protected array $materialCategories = [
        'paint_grade' => 'Paint Grade',
        'stain_grade' => 'Stain Grade',
        'premium' => 'Premium',
        'custom_exotic' => 'Custom/Exotic',
    ];

    // Finish options
    protected array $finishOptions = [
        'unfinished' => 'Unfinished',
        'natural_stain' => 'Natural Stain',
        'custom_stain' => 'Custom Stain',
        'paint_finish' => 'Paint Finish',
        'clear_coat' => 'Clear Coat',
    ];

    public function mount(int $projectId): void
    {
        $this->projectId = $projectId;
    }

    /**
     * Open modal to create a new entity
     */
    #[On('open-entity-creator')]
    public function openCreate(string $entityType, ?int $parentId = null): void
    {
        Log::info('Opening entity creator', [
            'type' => $entityType,
            'parentId' => $parentId,
            'projectId' => $this->projectId,
        ]);

        $this->entityType = $entityType;
        $this->mode = 'create';
        $this->entityId = null;
        $this->parentId = $parentId;
        $this->formData = $this->getDefaultFormData($entityType, $parentId);
        $this->showModal = true;
    }

    /**
     * Open modal to edit an existing entity
     */
    #[On('open-entity-editor')]
    public function openEdit(string $entityType, int $entityId): void
    {
        Log::info('Opening entity editor', [
            'type' => $entityType,
            'entityId' => $entityId,
        ]);

        $this->entityType = $entityType;
        $this->mode = 'edit';
        $this->entityId = $entityId;
        $this->parentId = null;
        $this->formData = $this->loadEntityData($entityType, $entityId);
        $this->showModal = true;
    }

    /**
     * Get default form data for creating a new entity
     */
    protected function getDefaultFormData(string $entityType, ?int $parentId): array
    {
        $defaults = match ($entityType) {
            'room' => [
                'name' => '',
                'room_type' => 'kitchen',
                'floor_number' => 1,
                'square_footage' => null,
                'notes' => '',
            ],
            'room_location' => [
                'name' => '',
                'location_type' => 'wall',
                'overall_width_inches' => null,
                'cabinet_level' => '2',
                'material_category' => '',
                'finish_option' => '',
                'notes' => '',
            ],
            'cabinet_run' => [
                'name' => '',
                'run_type' => 'base',
                'linear_feet' => null,
                'sort_order' => 0,
            ],
            'cabinet' => [
                'name' => '',
                'cabinet_type' => 'base',
                'length_inches' => 24,
                'depth_inches' => 24,
                'height_inches' => 30,
                'quantity' => 1,
                'position_in_run' => 0,
                'construction_style' => 'frameless',
                'door_style' => '',
                'material' => '',
                'finish' => '',
            ],
            'section' => [
                'section_type' => 'door',
                'width_inches' => 12,
                'height_inches' => 30,
                'hinge_side' => 'left',
                'overlay_style' => '',
            ],
            'door' => [
                'name' => '',
                'door_type' => 'standard',
                'width_inches' => 12,
                'height_inches' => 30,
                'hinge_side' => 'left',
            ],
            'drawer' => [
                'name' => '',
                'drawer_type' => 'standard',
                'width_inches' => 12,
                'height_inches' => 6,
                'depth_inches' => 18,
            ],
            'shelf' => [
                'name' => '',
                'shelf_type' => 'fixed',
                'width_inches' => 12,
                'depth_inches' => 18,
            ],
            'pullout' => [
                'name' => '',
                'pullout_type' => 'tray',
                'width_inches' => 12,
                'depth_inches' => 18,
            ],
            default => [],
        };

        return $defaults;
    }

    /**
     * Load existing entity data for editing
     */
    protected function loadEntityData(string $entityType, int $entityId): array
    {
        $entity = $this->getEntityModel($entityType)::find($entityId);

        if (!$entity) {
            return [];
        }

        return match ($entityType) {
            'room' => [
                'name' => $entity->name,
                'room_type' => $entity->room_type,
                'floor_number' => $entity->floor_number,
                'square_footage' => $entity->square_footage,
                'notes' => $entity->notes ?? '',
            ],
            'room_location' => [
                'name' => $entity->name,
                'location_type' => $entity->location_type,
                'overall_width_inches' => $entity->overall_width_inches,
                'cabinet_level' => $entity->cabinet_level,
                'material_category' => $entity->material_category ?? '',
                'finish_option' => $entity->finish_option ?? '',
                'notes' => $entity->notes ?? '',
            ],
            'cabinet_run' => [
                'name' => $entity->name,
                'run_type' => $entity->run_type,
                'linear_feet' => $entity->linear_feet,
                'sort_order' => $entity->sort_order ?? 0,
            ],
            'cabinet' => [
                'name' => $entity->name,
                'cabinet_type' => $entity->cabinet_type,
                'length_inches' => $entity->length_inches,
                'depth_inches' => $entity->depth_inches,
                'height_inches' => $entity->height_inches,
                'quantity' => $entity->quantity ?? 1,
                'position_in_run' => $entity->position_in_run ?? 0,
                'construction_style' => $entity->construction_style ?? 'frameless',
                'door_style' => $entity->door_style ?? '',
                'material' => $entity->material ?? '',
                'finish' => $entity->finish ?? '',
            ],
            default => $entity->toArray(),
        };
    }

    /**
     * Get the model class for an entity type
     */
    protected function getEntityModel(string $entityType): string
    {
        return match ($entityType) {
            'room' => Room::class,
            'room_location' => RoomLocation::class,
            'cabinet_run' => CabinetRun::class,
            'cabinet' => Cabinet::class,
            'section' => CabinetSection::class,
            'door' => Door::class,
            'drawer' => Drawer::class,
            'shelf' => Shelf::class,
            'pullout' => Pullout::class,
            default => throw new \Exception("Unknown entity type: {$entityType}"),
        };
    }

    /**
     * Save the entity (create or update)
     */
    public function save(): void
    {
        Log::info('Saving entity', [
            'type' => $this->entityType,
            'mode' => $this->mode,
            'formData' => $this->formData,
        ]);

        try {
            if ($this->mode === 'create') {
                $this->createEntity();
            } else {
                $this->updateEntity();
            }

            $this->dispatch('entity-saved', [
                'type' => $this->entityType,
                'mode' => $this->mode,
            ]);

            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('Failed to save entity', [
                'error' => $e->getMessage(),
                'type' => $this->entityType,
            ]);

            session()->flash('error', 'Failed to save: ' . $e->getMessage());
        }
    }

    /**
     * Create a new entity
     */
    protected function createEntity(): void
    {
        $model = $this->getEntityModel($this->entityType);
        $data = $this->prepareCreateData();

        Log::info('Creating entity', ['model' => $model, 'data' => $data]);

        $entity = $model::create($data);

        session()->flash('success', $this->entityLabels[$this->entityType] . ' created successfully.');
    }

    /**
     * Prepare data for creating an entity
     */
    protected function prepareCreateData(): array
    {
        $data = $this->formData;

        // Add parent relationships based on entity type
        switch ($this->entityType) {
            case 'room':
                $data['project_id'] = $this->projectId;
                $data['creator_id'] = auth()->id();
                break;

            case 'room_location':
                $data['room_id'] = $this->parentId;
                $data['creator_id'] = auth()->id();
                break;

            case 'cabinet_run':
                $data['room_location_id'] = $this->parentId;
                break;

            case 'cabinet':
                $data['cabinet_run_id'] = $this->parentId;
                // Get room_id from cabinet run
                $run = CabinetRun::find($this->parentId);
                if ($run && $run->roomLocation) {
                    $data['room_id'] = $run->roomLocation->room_id;
                }
                break;

            case 'section':
                $data['cabinet_id'] = $this->parentId;
                break;

            case 'door':
            case 'drawer':
            case 'shelf':
            case 'pullout':
                $data['cabinet_section_id'] = $this->parentId;
                break;
        }

        return $data;
    }

    /**
     * Update an existing entity
     */
    protected function updateEntity(): void
    {
        $model = $this->getEntityModel($this->entityType);
        $entity = $model::find($this->entityId);

        if (!$entity) {
            throw new \Exception('Entity not found');
        }

        $entity->update($this->formData);

        session()->flash('success', $this->entityLabels[$this->entityType] . ' updated successfully.');
    }

    /**
     * Delete the entity
     */
    public function delete(): void
    {
        Log::info('Deleting entity', [
            'type' => $this->entityType,
            'entityId' => $this->entityId,
        ]);

        try {
            $model = $this->getEntityModel($this->entityType);
            $entity = $model::find($this->entityId);

            if ($entity) {
                $entity->delete();
                session()->flash('success', $this->entityLabels[$this->entityType] . ' deleted successfully.');
            }

            $this->dispatch('entity-deleted', [
                'type' => $this->entityType,
                'entityId' => $this->entityId,
            ]);

            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('Failed to delete entity', [
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    /**
     * Close the modal and reset state
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['entityType', 'mode', 'entityId', 'parentId', 'formData']);
    }

    /**
     * Get modal heading based on mode and entity type
     */
    public function getModalHeading(): string
    {
        $label = $this->entityLabels[$this->entityType] ?? 'Entity';
        return $this->mode === 'create' ? "Create {$label}" : "Edit {$label}";
    }

    /**
     * Get options for select fields
     */
    public function getRoomTypes(): array
    {
        return $this->roomTypes;
    }

    public function getLocationTypes(): array
    {
        return $this->locationTypes;
    }

    public function getRunTypes(): array
    {
        return $this->runTypes;
    }

    public function getCabinetTypes(): array
    {
        return $this->cabinetTypes;
    }

    public function getPricingTiers(): array
    {
        return $this->pricingTiers;
    }

    public function getMaterialCategories(): array
    {
        return $this->materialCategories;
    }

    public function getFinishOptions(): array
    {
        return $this->finishOptions;
    }

    public function render()
    {
        return view('webkul-project::livewire.entity-crud-modal');
    }
}
