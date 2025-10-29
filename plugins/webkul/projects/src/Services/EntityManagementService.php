<?php

namespace Webkul\Project\Services;

use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;

class EntityManagementService
{
    /**
     * Get the entity ID field name for an annotation type
     *
     * @param  string  $annotationType  The annotation type (room, location, cabinet_run, cabinet)
     * @return string The entity ID field name (e.g., 'room_id', 'room_location_id')
     *
     * @throws \InvalidArgumentException If annotation type is unknown
     */
    public function getEntityIdField(string $annotationType): string
    {
        return match ($annotationType) {
            'room'        => 'room_id',
            'location'    => 'room_location_id',
            'cabinet_run' => 'cabinet_run_id',
            'cabinet'     => 'cabinet_specification_id',
            default       => throw new \InvalidArgumentException("Unknown annotation type: {$annotationType}"),
        };
    }

    /**
     * Get the entity name field for an annotation type
     *
     * @param  string  $annotationType  The annotation type (room, location, cabinet_run, cabinet)
     * @return string The name field name (e.g., 'name', 'cabinet_number')
     *
     * @throws \InvalidArgumentException If annotation type is unknown
     */
    public function getEntityNameField(string $annotationType): string
    {
        return match ($annotationType) {
            'room'        => 'name',
            'location'    => 'name',
            'cabinet_run' => 'name',
            'cabinet'     => 'cabinet_number',
            default       => throw new \InvalidArgumentException("Unknown annotation type: {$annotationType}"),
        };
    }

    /**
     * Get the name value from an entity instance
     *
     * @param  string  $annotationType  The annotation type
     * @param  Model  $entity  The entity instance
     * @return string The name value
     */
    public function getEntityName(string $annotationType, Model $entity): string
    {
        $nameField = $this->getEntityNameField($annotationType);

        return $entity->$nameField ?? '';
    }

    /**
     * Get the entity model class name for an annotation type
     *
     * @param  string  $annotationType  The annotation type
     * @return string The fully qualified model class name
     *
     * @throws \InvalidArgumentException If annotation type is unknown
     */
    public function getEntityModel(string $annotationType): string
    {
        return match ($annotationType) {
            'room'        => Room::class,
            'location'    => RoomLocation::class,
            'cabinet_run' => CabinetRun::class,
            'cabinet'     => CabinetSpecification::class,
            default       => throw new \InvalidArgumentException("Unknown annotation type: {$annotationType}"),
        };
    }

    /**
     * Load an entity by annotation type and ID
     *
     * @param  string  $annotationType  The annotation type
     * @param  int  $entityId  The entity ID
     * @return Model|null The entity model or null if not found
     */
    public function loadEntity(string $annotationType, int $entityId): ?Model
    {
        $modelClass = $this->getEntityModel($annotationType);

        return $modelClass::find($entityId);
    }

    /**
     * Create a new entity from form data
     *
     * @param  string  $annotationType  The annotation type
     * @param  array  $entityData  The entity data from form
     * @param  int  $projectId  The project ID
     * @param  int|null  $parentEntityId  The parent entity ID (e.g., room_id for location, cabinet_run_id for cabinet)
     * @return Model The created entity
     */
    public function createEntity(string $annotationType, array $entityData, int $projectId, ?int $parentEntityId = null): Model
    {
        $modelClass = $this->getEntityModel($annotationType);

        // Add project_id and creator_id
        if ($annotationType === 'room') {
            $entityData['project_id'] = $projectId;
        }

        $entityData['creator_id'] = auth()->id();

        // Add parent entity ID based on annotation type
        if ($parentEntityId) {
            if ($annotationType === 'location') {
                $entityData['room_id'] = $parentEntityId;
            } elseif ($annotationType === 'cabinet_run') {
                $entityData['room_location_id'] = $parentEntityId;
            } elseif ($annotationType === 'cabinet') {
                $entityData['cabinet_run_id'] = $parentEntityId;
                $entityData['project_id'] = $projectId;
            }
        }

        // Handle cabinet-specific required fields with defaults
        if ($annotationType === 'cabinet') {
            $entityData['length_inches'] = $entityData['length_inches'] ?? 0;
            $entityData['width_inches'] = $entityData['width_inches'] ?? 0;
            $entityData['depth_inches'] = $entityData['depth_inches'] ?? 0;
            $entityData['height_inches'] = $entityData['height_inches'] ?? 0;
            $entityData['linear_feet'] = $entityData['linear_feet'] ?? 0;
            $entityData['quantity'] = $entityData['quantity'] ?? 1;
            $entityData['unit_price_per_lf'] = $entityData['unit_price_per_lf'] ?? 0;
            $entityData['total_price'] = $entityData['total_price'] ?? 0;
            $entityData['product_variant_id'] = $entityData['product_variant_id'] ?? null;
        }

        return $modelClass::create($entityData);
    }

    /**
     * Update an existing entity
     *
     * @param  string  $annotationType  The annotation type
     * @param  int  $entityId  The entity ID
     * @param  array  $entityData  The entity data to update
     * @return bool True if updated successfully
     */
    public function updateEntity(string $annotationType, int $entityId, array $entityData): bool
    {
        $entity = $this->loadEntity($annotationType, $entityId);

        if (! $entity) {
            return false;
        }

        return $entity->update($entityData);
    }

    /**
     * Find an existing entity by name and parent ID
     *
     * @param  string  $annotationType  The annotation type
     * @param  string  $name  The entity name to search for
     * @param  int|null  $parentId  The parent entity ID (room_id, room_location_id, etc.)
     * @return Model|null The existing entity or null
     */
    public function findExistingEntity(string $annotationType, string $name, ?int $parentId): ?Model
    {
        $modelClass = $this->getEntityModel($annotationType);

        // Get the correct name field for the entity type
        $nameField = $this->getEntityNameField($annotationType);

        $query = $modelClass::where($nameField, $name);

        // Add parent constraint based on annotation type
        if ($annotationType === 'location' && $parentId) {
            $query->where('room_id', $parentId);
        } elseif ($annotationType === 'cabinet_run' && $parentId) {
            $query->where('room_location_id', $parentId);
        } elseif ($annotationType === 'cabinet' && $parentId) {
            $query->where('cabinet_run_id', $parentId);
        }

        return $query->first();
    }

    /**
     * Get hierarchical entity options for dropdown selector
     * Returns options like:
     * - Room: ["1" => "K1"]
     * - Location: ["10" => "K1 > Sink Wall"]
     * - Cabinet Run: ["25" => "K1 > Sink Wall > Upper Cabinets"]
     * - Cabinet: ["38" => "K1 > Sink Wall > Upper Cabinets > Cabinet #1"]
     *
     * @param  string  $annotationType  The annotation type
     * @param  int  $projectId  The project ID
     * @return array Hierarchical options [id => label]
     */
    public function getHierarchicalEntityOptions(string $annotationType, int $projectId): array
    {
        return match ($annotationType) {
            'room'        => $this->getRoomOptions($projectId),
            'location'    => $this->getLocationOptions($projectId),
            'cabinet_run' => $this->getCabinetRunOptions($projectId),
            'cabinet'     => $this->getCabinetOptions($projectId),
            default       => [],
        };
    }

    /**
     * Get room options for dropdown
     */
    protected function getRoomOptions(int $projectId): array
    {
        return Room::where('project_id', $projectId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get location options with room hierarchy
     */
    protected function getLocationOptions(int $projectId): array
    {
        $locations = RoomLocation::whereHas('room', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })
            ->with('room')
            ->orderBy('name')
            ->get();

        $options = [];
        foreach ($locations as $location) {
            $roomName = $location->room?->name ?? 'Unknown Room';
            $options[$location->id] = "{$roomName} > {$location->name}";
        }

        return $options;
    }

    /**
     * Get cabinet run options with location > room hierarchy
     */
    protected function getCabinetRunOptions(int $projectId): array
    {
        $cabinetRuns = CabinetRun::whereHas('roomLocation.room', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })
            ->with(['roomLocation.room'])
            ->orderBy('name')
            ->get();

        $options = [];
        foreach ($cabinetRuns as $run) {
            $location = $run->roomLocation;
            $room = $location?->room;

            $roomName = $room?->name ?? 'Unknown Room';
            $locationName = $location?->name ?? 'Unknown Location';

            $options[$run->id] = "{$roomName} > {$locationName} > {$run->name}";
        }

        return $options;
    }

    /**
     * Get cabinet options with run > location > room hierarchy
     */
    protected function getCabinetOptions(int $projectId): array
    {
        $cabinets = CabinetSpecification::where('project_id', $projectId)
            ->with(['cabinetRun.roomLocation.room'])
            ->orderBy('cabinet_number')
            ->get();

        $options = [];
        foreach ($cabinets as $cabinet) {
            $run = $cabinet->cabinetRun;
            $location = $run?->roomLocation;
            $room = $location?->room;

            $roomName = $room?->name ?? 'Unknown Room';
            $locationName = $location?->name ?? 'Unknown Location';
            $runName = $run?->name ?? 'Unknown Run';
            $cabinetLabel = $cabinet->cabinet_number ?? "Cabinet #{$cabinet->id}";

            $options[$cabinet->id] = "{$roomName} > {$locationName} > {$runName} > {$cabinetLabel}";
        }

        return $options;
    }
}
