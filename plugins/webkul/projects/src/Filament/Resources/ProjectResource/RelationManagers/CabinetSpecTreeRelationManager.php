<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;
use Webkul\Project\Models\Project;

/**
 * Cabinet Spec Tree Relation Manager
 *
 * This is a "virtual" relation manager that displays the CabinetSpecBuilder
 * component for hierarchical cabinet entry (Room → Location → Run → Cabinet)
 *
 * Uses a minimal table with custom header to embed the CabinetSpecBuilder
 */
class CabinetSpecTreeRelationManager extends RelationManager
{
    protected static string $relationship = 'rooms';

    protected static ?string $title = 'Cabinet Spec (Tree)';

    protected static \BackedEnum|string|null $icon = 'heroicon-o-rectangle-group';

    /**
     * Property to store the loaded spec data
     */
    public array $specData = [];

    /**
     * Mount the component and load spec data
     */
    public function mount(): void
    {
        parent::mount();
        $this->specData = $this->loadSpecDataFromDatabase();
    }

    /**
     * Define the table with custom header containing the CabinetSpecBuilder
     */
    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([])
            ->contentFooter(
                view('webkul-project::filament.relation-managers.cabinet-spec-tree-content', [
                    'project' => $this->getOwnerRecord(),
                    'specData' => $this->loadSpecDataFromDatabase(),
                ])
            )
            ->emptyStateHeading('')
            ->emptyStateDescription('');
    }

    /**
     * Override isReadOnly to allow interactions
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Load spec data from database relations
     */
    public function loadSpecDataFromDatabase(): array
    {
        /** @var Project $project */
        $project = $this->getOwnerRecord();

        if (!$project) {
            return [];
        }

        $specData = [];

        // Load rooms with nested relations
        $rooms = $project->rooms()
            ->with([
                'locations.cabinetRuns.cabinets' => function ($query) {
                    $query->orderBy('position_in_run');
                },
                'locations' => function ($query) {
                    $query->orderBy('sort_order');
                },
            ])
            ->orderBy('sort_order')
            ->get();

        foreach ($rooms as $room) {
            $roomNode = [
                'id' => 'room_' . $room->id,
                'db_id' => $room->id,
                'type' => 'room',
                'name' => $room->name,
                'room_type' => $room->room_type,
                'floor_number' => $room->floor_number,
                'notes' => $room->notes,
                'linear_feet' => 0,
                'estimated_price' => 0,
                'children' => [],
            ];

            foreach ($room->locations as $location) {
                $locationNode = [
                    'id' => 'location_' . $location->id,
                    'db_id' => $location->id,
                    'type' => 'room_location',
                    'name' => $location->name,
                    'location_type' => $location->location_type,
                    'cabinet_level' => $location->cabinet_level,
                    'notes' => $location->notes,
                    'linear_feet' => 0,
                    'estimated_price' => 0,
                    'children' => [],
                ];

                foreach ($location->cabinetRuns as $run) {
                    $runNode = [
                        'id' => 'run_' . $run->id,
                        'db_id' => $run->id,
                        'type' => 'cabinet_run',
                        'name' => $run->name,
                        'run_type' => $run->run_type,
                        'notes' => $run->notes,
                        'linear_feet' => 0,
                        'children' => [],
                    ];

                    foreach ($run->cabinets as $cabinet) {
                        $cabinetLF = ($cabinet->length_inches / 12) * ($cabinet->quantity ?? 1);
                        $cabinetNode = [
                            'id' => 'cabinet_' . $cabinet->id,
                            'db_id' => $cabinet->id,
                            'type' => 'cabinet',
                            'name' => $cabinet->cabinet_number ?? $cabinet->full_code ?? 'Cabinet',
                            'cabinet_type' => $cabinet->cabinet_level ?? 'base',
                            'length_inches' => $cabinet->length_inches,
                            'depth_inches' => $cabinet->depth_inches,
                            'height_inches' => $cabinet->height_inches,
                            'quantity' => $cabinet->quantity ?? 1,
                            'linear_feet' => $cabinetLF,
                        ];
                        $runNode['children'][] = $cabinetNode;
                        $runNode['linear_feet'] += $cabinetLF;
                    }

                    $locationNode['children'][] = $runNode;
                    $locationNode['linear_feet'] += $runNode['linear_feet'];
                }

                // Calculate price based on cabinet level
                $pricePerLF = $this->getPricePerLF($locationNode['cabinet_level'] ?? 2);
                $locationNode['estimated_price'] = $locationNode['linear_feet'] * $pricePerLF;

                $roomNode['children'][] = $locationNode;
                $roomNode['linear_feet'] += $locationNode['linear_feet'];
                $roomNode['estimated_price'] += $locationNode['estimated_price'];
            }

            $specData[] = $roomNode;
        }

        return $specData;
    }

    /**
     * Get price per linear foot based on cabinet level
     */
    protected function getPricePerLF(int $level): float
    {
        return match ($level) {
            1 => 138.00,
            2 => 168.00,
            3 => 192.00,
            4 => 210.00,
            5 => 225.00,
            default => 168.00,
        };
    }

    /**
     * Handle spec data updates from CabinetSpecBuilder component
     */
    #[On('spec-data-updated')]
    public function handleSpecDataUpdate(array $data): void
    {
        /** @var Project $project */
        $project = $this->getOwnerRecord();

        if (!$project) {
            return;
        }

        $this->syncSpecDataToDatabase($project, $data);

        // Notify user
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Cabinet specifications updated')
            ->send();
    }

    /**
     * Sync spec data back to database
     */
    protected function syncSpecDataToDatabase(Project $project, array $specData): void
    {
        // Track existing IDs to detect deletions
        $existingRoomIds = $project->rooms()->pluck('id')->toArray();
        $processedRoomIds = [];

        foreach ($specData as $roomData) {
            $roomDbId = $roomData['db_id'] ?? null;

            if ($roomDbId) {
                // Update existing room
                $room = $project->rooms()->find($roomDbId);
                if ($room) {
                    $room->update([
                        'name' => $roomData['name'],
                        'room_type' => $roomData['room_type'] ?? 'other',
                        'floor_number' => $roomData['floor_number'] ?? 1,
                        'notes' => $roomData['notes'] ?? null,
                    ]);
                    $processedRoomIds[] = $roomDbId;
                }
            } else {
                // Create new room
                $room = $project->rooms()->create([
                    'name' => $roomData['name'],
                    'room_type' => $roomData['room_type'] ?? 'other',
                    'floor_number' => $roomData['floor_number'] ?? 1,
                    'notes' => $roomData['notes'] ?? null,
                    'creator_id' => auth()->id(),
                ]);
                $processedRoomIds[] = $room->id;
            }

            // Sync locations for this room
            if ($room && !empty($roomData['children'])) {
                $this->syncLocations($room, $roomData['children']);
            }
        }

        // Delete rooms that were removed
        $roomsToDelete = array_diff($existingRoomIds, $processedRoomIds);
        if (!empty($roomsToDelete)) {
            $project->rooms()->whereIn('id', $roomsToDelete)->delete();
        }
    }

    /**
     * Sync locations for a room
     */
    protected function syncLocations($room, array $locations): void
    {
        $existingIds = $room->locations()->pluck('id')->toArray();
        $processedIds = [];

        foreach ($locations as $locationData) {
            $dbId = $locationData['db_id'] ?? null;

            if ($dbId) {
                $location = $room->locations()->find($dbId);
                if ($location) {
                    $location->update([
                        'name' => $locationData['name'],
                        'location_type' => $locationData['location_type'] ?? 'wall',
                        'cabinet_level' => $locationData['cabinet_level'] ?? 2,
                        'notes' => $locationData['notes'] ?? null,
                    ]);
                    $processedIds[] = $dbId;
                }
            } else {
                $location = $room->locations()->create([
                    'project_id' => $room->project_id,
                    'name' => $locationData['name'],
                    'location_type' => $locationData['location_type'] ?? 'wall',
                    'cabinet_level' => $locationData['cabinet_level'] ?? 2,
                    'notes' => $locationData['notes'] ?? null,
                    'creator_id' => auth()->id(),
                ]);
                $processedIds[] = $location->id;
            }

            // Sync runs for this location
            if ($location && !empty($locationData['children'])) {
                $this->syncRuns($location, $locationData['children']);
            }
        }

        // Delete locations that were removed
        $toDelete = array_diff($existingIds, $processedIds);
        if (!empty($toDelete)) {
            $room->locations()->whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Sync cabinet runs for a location
     */
    protected function syncRuns($location, array $runs): void
    {
        $existingIds = $location->cabinetRuns()->pluck('id')->toArray();
        $processedIds = [];

        foreach ($runs as $runData) {
            $dbId = $runData['db_id'] ?? null;

            if ($dbId) {
                $run = $location->cabinetRuns()->find($dbId);
                if ($run) {
                    $run->update([
                        'name' => $runData['name'],
                        'run_type' => $runData['run_type'] ?? 'base',
                        'notes' => $runData['notes'] ?? null,
                    ]);
                    $processedIds[] = $dbId;
                }
            } else {
                $run = $location->cabinetRuns()->create([
                    'project_id' => $location->project_id,
                    'name' => $runData['name'],
                    'run_type' => $runData['run_type'] ?? 'base',
                    'notes' => $runData['notes'] ?? null,
                    'creator_id' => auth()->id(),
                ]);
                $processedIds[] = $run->id;
            }

            // Sync cabinets for this run
            if ($run && !empty($runData['children'])) {
                $this->syncCabinets($run, $runData['children']);
            }
        }

        // Delete runs that were removed
        $toDelete = array_diff($existingIds, $processedIds);
        if (!empty($toDelete)) {
            $location->cabinetRuns()->whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Sync cabinets for a cabinet run
     */
    protected function syncCabinets($run, array $cabinets): void
    {
        $existingIds = $run->cabinets()->pluck('id')->toArray();
        $processedIds = [];
        $sortOrder = 1;

        foreach ($cabinets as $cabinetData) {
            $dbId = $cabinetData['db_id'] ?? null;

            $cabinetFields = [
                'cabinet_number' => $cabinetData['name'],
                'length_inches' => $cabinetData['length_inches'] ?? 24,
                'depth_inches' => $cabinetData['depth_inches'] ?? 24,
                'height_inches' => $cabinetData['height_inches'] ?? 34.5,
                'quantity' => $cabinetData['quantity'] ?? 1,
                'position_in_run' => $sortOrder++,
            ];

            if ($dbId) {
                $cabinet = $run->cabinets()->find($dbId);
                if ($cabinet) {
                    $cabinet->update($cabinetFields);
                    $processedIds[] = $dbId;
                }
            } else {
                $cabinet = $run->cabinets()->create(array_merge($cabinetFields, [
                    'project_id' => $run->project_id,
                    'room_id' => $run->roomLocation->room_id,
                    'creator_id' => auth()->id(),
                ]));
                $processedIds[] = $cabinet->id;
            }
        }

        // Delete cabinets that were removed
        $toDelete = array_diff($existingIds, $processedIds);
        if (!empty($toDelete)) {
            $run->cabinets()->whereIn('id', $toDelete)->delete();
        }
    }
}
