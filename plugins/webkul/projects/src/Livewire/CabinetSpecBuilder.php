<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Cabinet Spec Builder
 *
 * A Notion-like hierarchical tree builder for cabinet specifications.
 * Hierarchy: Room → RoomLocation → CabinetRun → Cabinet
 *
 * Data is stored as JSON in draft, entities created on project save.
 */
class CabinetSpecBuilder extends Component
{
    // Tree data structure
    public array $specData = [];
    public array $expanded = [];

    // Calculated totals
    public float $totalLinearFeet = 0;
    public float $totalPrice = 0;

    // Modal state (for Room, Location, Run - NOT cabinets)
    public bool $showModal = false;
    public string $modalEntityType = '';
    public string $modalMode = 'create'; // create or edit
    public ?string $parentPath = null;
    public ?string $editPath = null;
    public array $formData = [];

    // Inline cabinet entry state (for rapid cabinet entry)
    public bool $isAddingCabinet = false;
    public ?string $addingToRunPath = null;
    public array $newCabinetData = [];
    public string $activeField = 'name';

    // Entity type labels
    protected array $entityLabels = [
        'room' => 'Room',
        'room_location' => 'Room Location',
        'cabinet_run' => 'Cabinet Run',
        'cabinet' => 'Cabinet',
    ];

    // Room types
    public array $roomTypes = [
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
    public array $locationTypes = [
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
    public array $runTypes = [
        'base' => 'Base Cabinets',
        'wall' => 'Wall Cabinets',
        'tall' => 'Tall Cabinets',
        'island' => 'Island',
    ];

    // Cabinet types
    public array $cabinetTypes = [
        'base' => 'Base',
        'wall' => 'Wall',
        'tall' => 'Tall',
        'vanity' => 'Vanity',
        'specialty' => 'Specialty',
    ];

    // Pricing tiers
    public array $pricingTiers = [
        '1' => 'Level 1 - Basic',
        '2' => 'Level 2 - Standard',
        '3' => 'Level 3 - Enhanced',
        '4' => 'Level 4 - Premium',
        '5' => 'Level 5 - Custom',
    ];

    public function mount(array $specData = []): void
    {
        if (!empty($specData)) {
            $this->specData = $specData;
        }
        $this->calculateTotals();
    }

    /**
     * Livewire updated hook - triggers smart detection when cabinet name changes
     */
    public function updated($property, $value): void
    {
        // Smart detection when cabinet name is updated via wire:model
        if ($property === 'newCabinetData.name' && $value && $this->isAddingCabinet) {
            $parsed = $this->parseFromName($value);

            if ($parsed['type']) {
                $this->newCabinetData['cabinet_type'] = $parsed['type'];
                $defaults = $this->typeDefaults[$parsed['type']] ?? [];
                $this->newCabinetData['depth_inches'] = $defaults['depth_inches'] ?? 24;
                $this->newCabinetData['height_inches'] = $defaults['height_inches'] ?? 34.5;
            }

            if ($parsed['width']) {
                $this->newCabinetData['length_inches'] = $parsed['width'];
            }
        }
    }

    /**
     * Open modal to create a new entity
     */
    public function openCreate(string $type, ?string $parentPath = null): void
    {
        Log::info('CabinetSpecBuilder: Opening create modal', [
            'type' => $type,
            'parentPath' => $parentPath,
        ]);

        $this->modalEntityType = $type;
        $this->modalMode = 'create';
        $this->parentPath = $parentPath;
        $this->editPath = null;
        $this->formData = $this->getDefaultFormData($type);
        $this->showModal = true;
    }

    /**
     * Open modal to edit an existing entity
     * Supports both single-arg (path) and two-arg (type, path) calls
     */
    public function openEdit(string $typeOrPath, ?string $path = null): void
    {
        // Support both call styles: openEdit('path') and openEdit('type', 'path')
        $actualPath = $path ?? $typeOrPath;

        $node = $this->getNodeByPath($actualPath);

        if (!$node) {
            Log::warning('CabinetSpecBuilder: Node not found for edit', ['path' => $actualPath]);
            return;
        }

        Log::info('CabinetSpecBuilder: Opening edit modal', [
            'path' => $actualPath,
            'node' => $node,
        ]);

        $this->modalEntityType = $node['type'];
        $this->modalMode = 'edit';
        $this->editPath = $actualPath;
        $this->parentPath = null;
        $this->formData = $node;
        $this->showModal = true;
    }

    /**
     * Save the entity (create or update)
     */
    public function save(): void
    {
        Log::info('CabinetSpecBuilder: Saving entity', [
            'mode' => $this->modalMode,
            'type' => $this->modalEntityType,
            'formData' => $this->formData,
        ]);

        if ($this->modalMode === 'create') {
            $this->createEntity();
        } else {
            $this->updateEntity();
        }

        $this->calculateTotals();
        $this->closeModal();
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Create a new entity and add to tree
     */
    protected function createEntity(): void
    {
        $newNode = array_merge($this->formData, [
            'id' => $this->modalEntityType . '_' . Str::random(8),
            'type' => $this->modalEntityType,
            'source' => 'user', // Track as user-created
            'created_at' => now()->toIso8601String(),
            'children' => [],
        ]);

        if ($this->parentPath === null) {
            // Add to root (rooms only)
            $this->specData[] = $newNode;
        } else {
            // Add as child of parent
            $this->addChildToPath($this->parentPath, $newNode);
        }

        // Auto-expand parent
        if ($this->parentPath !== null) {
            $parentNode = $this->getNodeByPath($this->parentPath);
            if ($parentNode && isset($parentNode['id'])) {
                $this->expanded[] = $parentNode['id'];
            }
        }
    }

    /**
     * Update an existing entity
     */
    protected function updateEntity(): void
    {
        if (!$this->editPath) {
            return;
        }

        $this->updateNodeByPath($this->editPath, $this->formData);
    }

    /**
     * Delete an entity and its children
     */
    public function delete(string $path): void
    {
        Log::info('CabinetSpecBuilder: Deleting entity', ['path' => $path]);

        $this->deleteNodeByPath($path);
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Delete an entity by type and path (convenience method for UI)
     * Supports both call styles: deleteByPath('type', 'path') and deleteByPath('path')
     */
    public function deleteByPath(string $typeOrPath, ?string $path = null): void
    {
        $actualPath = $path ?? $typeOrPath;
        $this->delete($actualPath);
    }

    /**
     * Toggle node expansion
     */
    public function toggleExpanded(string $nodeId): void
    {
        if (in_array($nodeId, $this->expanded)) {
            $this->expanded = array_filter($this->expanded, fn($id) => $id !== $nodeId);
        } else {
            $this->expanded[] = $nodeId;
        }
    }

    /**
     * Check if a node is expanded
     */
    public function isExpanded(string $nodeId): bool
    {
        return in_array($nodeId, $this->expanded);
    }

    /**
     * Close modal and reset state
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['modalEntityType', 'modalMode', 'parentPath', 'editPath', 'formData']);
    }

    /**
     * Get default form data for a new entity
     */
    protected function getDefaultFormData(string $type): array
    {
        return match ($type) {
            'room' => [
                'name' => '',
                'room_type' => 'kitchen',
                'floor_number' => 1,
            ],
            'room_location' => [
                'name' => '',
                'location_type' => 'wall',
                'cabinet_level' => '2',
            ],
            'cabinet_run' => [
                'name' => '',
                'run_type' => 'base',
            ],
            'cabinet' => [
                'name' => '',
                'cabinet_type' => 'base',
                'length_inches' => 24,
                'depth_inches' => 24,
                'height_inches' => 30,
                'quantity' => 1,
            ],
            default => ['name' => ''],
        };
    }

    /**
     * Calculate totals (rollup from bottom to top)
     */
    public function calculateTotals(): void
    {
        foreach ($this->specData as &$room) {
            $room['linear_feet'] = 0;
            $room['estimated_price'] = 0;

            foreach ($room['children'] ?? [] as &$location) {
                $location['linear_feet'] = 0;
                $location['estimated_price'] = 0;

                foreach ($location['children'] ?? [] as &$run) {
                    $run['linear_feet'] = 0;

                    // Sum cabinets in this run
                    foreach ($run['children'] ?? [] as $cabinet) {
                        $cabinetLF = ($cabinet['length_inches'] ?? 0) / 12 * ($cabinet['quantity'] ?? 1);
                        $run['linear_feet'] += $cabinetLF;
                    }

                    // Roll up to location
                    $location['linear_feet'] += $run['linear_feet'];
                }

                // Calculate price for location (LF × pricing tier rate)
                $cabinetLevel = $location['cabinet_level'] ?? '2';
                $location['estimated_price'] = $location['linear_feet'] * $this->getPricePerLF((int) $cabinetLevel);

                // Roll up to room
                $room['linear_feet'] += $location['linear_feet'];
                $room['estimated_price'] += $location['estimated_price'];
            }
        }

        // Calculate grand totals
        $this->totalLinearFeet = array_sum(array_column($this->specData, 'linear_feet'));
        $this->totalPrice = array_sum(array_column($this->specData, 'estimated_price'));
    }

    /**
     * Get price per linear foot based on cabinet level tier
     */
    protected function getPricePerLF(int $tier): float
    {
        return match ($tier) {
            1 => 225,   // Level 1 - Basic
            2 => 298,   // Level 2 - Standard
            3 => 348,   // Level 3 - Enhanced
            4 => 425,   // Level 4 - Premium
            5 => 550,   // Level 5 - Custom
            default => 298,
        };
    }

    /**
     * Get node by dot-notation path (e.g., "0.children.1.children.0")
     */
    protected function getNodeByPath(string $path): ?array
    {
        $parts = explode('.', $path);
        $current = $this->specData;

        foreach ($parts as $part) {
            if ($part === 'children') {
                // Navigate into children array
                if (!isset($current['children'])) {
                    return null;
                }
                $current = $current['children'];
            } else {
                if (!isset($current[$part])) {
                    return null;
                }
                $current = $current[$part];
            }
        }

        return is_array($current) ? $current : null;
    }

    /**
     * Add child node to parent at path
     */
    protected function addChildToPath(string $path, array $child): void
    {
        $parts = explode('.', $path);
        $ref = &$this->specData;

        foreach ($parts as $part) {
            if ($part === 'children') {
                if (!isset($ref['children'])) {
                    $ref['children'] = [];
                }
                $ref = &$ref['children'];
            } else {
                $ref = &$ref[$part];
            }
        }

        if (!isset($ref['children'])) {
            $ref['children'] = [];
        }

        // AUTO-NAME CABINETS: If adding a cabinet to a run, auto-generate sequential name
        if ($child['type'] === 'cabinet' && ($ref['type'] ?? '') === 'cabinet_run') {
            $child = $this->autoNameCabinet($child, $ref);
        }

        $ref['children'][] = $child;
    }

    /**
     * Auto-generate sequential name for a cabinet based on its run
     * Ensures all cabinets get proper names like B1, B2, W1, T1 regardless of source
     */
    protected function autoNameCabinet(array $cabinet, array $run): array
    {
        $runType = $run['run_type'] ?? 'base';
        $existingCount = count($run['children'] ?? []);
        $nextNum = $existingCount + 1;

        $prefix = $this->getRunPrefix($runType);
        $sequentialName = $prefix . $nextNum;

        // If cabinet already has a name that looks like a code (DB18, SB36), move it to 'code'
        $currentName = $cabinet['name'] ?? '';
        if (!empty($currentName) && preg_match('/^[A-Z]{1,3}\d{2,3}/', strtoupper($currentName))) {
            // Current name looks like a cabinet code - store it as code
            $cabinet['code'] = $cabinet['code'] ?? strtoupper($currentName);
        }

        // Always set the sequential name
        $cabinet['name'] = $sequentialName;
        $cabinet['position_in_run'] = $nextNum;

        return $cabinet;
    }

    /**
     * Update node at path with new data
     */
    protected function updateNodeByPath(string $path, array $data): void
    {
        $parts = explode('.', $path);
        $ref = &$this->specData;

        foreach ($parts as $part) {
            if ($part === 'children') {
                $ref = &$ref['children'];
            } else {
                $ref = &$ref[$part];
            }
        }

        // Preserve children and id when updating
        $children = $ref['children'] ?? [];
        $id = $ref['id'] ?? null;
        $type = $ref['type'] ?? null;

        $ref = array_merge($ref, $data);
        $ref['children'] = $children;

        if ($id) {
            $ref['id'] = $id;
        }
        if ($type) {
            $ref['type'] = $type;
        }
    }

    /**
     * Delete node at path
     */
    protected function deleteNodeByPath(string $path): void
    {
        $parts = explode('.', $path);
        $lastPart = array_pop($parts);

        if (empty($parts)) {
            // Deleting from root
            unset($this->specData[$lastPart]);
            $this->specData = array_values($this->specData);
            return;
        }

        $ref = &$this->specData;

        foreach ($parts as $part) {
            if ($part === 'children') {
                $ref = &$ref['children'];
            } else {
                $ref = &$ref[$part];
            }
        }

        // Navigate into children if the last part before index is 'children'
        if (isset($ref['children'][$lastPart])) {
            unset($ref['children'][$lastPart]);
            $ref['children'] = array_values($ref['children']);
        } elseif (isset($ref[$lastPart])) {
            unset($ref[$lastPart]);
            if (is_array($ref)) {
                $ref = array_values($ref);
            }
        }
    }

    /**
     * Dispatch spec data update to parent form
     */
    protected function dispatchSpecDataUpdate(): void
    {
        $this->dispatch('spec-data-updated', data: $this->specData);
    }

    /**
     * Restore spec data from draft
     */
    #[On('restore-spec-data')]
    public function restoreSpecData(array $data): void
    {
        $this->specData = $data;
        $this->calculateTotals();
    }

    /**
     * Get modal heading
     */
    public function getModalHeading(): string
    {
        $label = $this->entityLabels[$this->modalEntityType] ?? 'Entity';
        return $this->modalMode === 'create' ? "Add {$label}" : "Edit {$label}";
    }

    /**
     * Get child type for a given entity type
     */
    public function getChildType(string $type): ?string
    {
        return match ($type) {
            'room' => 'room_location',
            'room_location' => 'cabinet_run',
            'cabinet_run' => 'cabinet',
            default => null,
        };
    }

    /**
     * Get child label for a given entity type
     */
    public function getChildLabel(string $type): ?string
    {
        return match ($type) {
            'room' => 'Location',
            'room_location' => 'Run',
            'cabinet_run' => 'Cabinet',
            default => null,
        };
    }

    // =========================================================================
    // INLINE CABINET ENTRY METHODS (rapid entry, no modal)
    // =========================================================================

    /**
     * Smart defaults by cabinet type
     */
    protected array $typeDefaults = [
        'base' => ['depth_inches' => 24, 'height_inches' => 34.5],
        'wall' => ['depth_inches' => 12, 'height_inches' => 30],
        'tall' => ['depth_inches' => 24, 'height_inches' => 84],
        'vanity' => ['depth_inches' => 21, 'height_inches' => 34.5],
    ];

    /**
     * Start inline cabinet entry
     */
    public function startAddCabinet(string $runPath): void
    {
        $runNode = $this->getNodeByPath($runPath);
        $runType = $runNode['run_type'] ?? 'base';

        // Get next cabinet number for this run
        $nextNumber = $this->getNextCabinetNumber($runPath, $runType);

        $this->isAddingCabinet = true;
        $this->addingToRunPath = $runPath;
        $this->activeField = 'name';
        $this->newCabinetData = [
            'name' => $nextNumber,  // Auto-fill with next number (B1, B2, etc.)
            'cabinet_type' => $runType === 'island' ? 'base' : $runType,
            'length_inches' => null,
            'depth_inches' => $this->typeDefaults[$runType]['depth_inches'] ?? 24,
            'height_inches' => $this->typeDefaults[$runType]['height_inches'] ?? 34.5,
            'quantity' => 1,
        ];

        // Auto-expand the run to show the inline table
        if ($runNode && isset($runNode['id'])) {
            if (!in_array($runNode['id'], $this->expanded)) {
                $this->expanded[] = $runNode['id'];
            }
        }

        Log::info('CabinetSpecBuilder: Starting inline cabinet entry', [
            'runPath' => $runPath,
            'nextNumber' => $nextNumber,
        ]);
    }

    /**
     * Get next auto-generated cabinet number for a run
     * Format: B1, B2, B3... for base; W1, W2... for wall; etc.
     */
    protected function getNextCabinetNumber(string $runPath, string $runType): string
    {
        $prefix = match ($runType) {
            'base', 'island' => 'B',
            'wall' => 'W',
            'tall' => 'T',
            default => 'C',
        };

        $runNode = $this->getNodeByPath($runPath);
        $existingCabinets = $runNode['children'] ?? [];

        // Count existing cabinets with same prefix
        $count = 0;
        foreach ($existingCabinets as $cabinet) {
            $name = $cabinet['name'] ?? '';
            if (preg_match('/^' . $prefix . '(\d+)/', strtoupper($name), $matches)) {
                $num = (int) $matches[1];
                if ($num > $count) {
                    $count = $num;
                }
            }
        }

        return $prefix . ($count + 1);
    }

    /**
     * Update a field in the new cabinet data (live)
     */
    public function updateNewCabinetField(string $field, mixed $value): void
    {
        // Parse width input (handles "24", "24in", "2ft", etc.)
        if ($field === 'length_inches' && is_string($value)) {
            $value = $this->parseWidthInput($value);
        }

        $this->newCabinetData[$field] = $value;

        // Smart detection from name
        if ($field === 'name' && $value) {
            $parsed = $this->parseFromName($value);

            if ($parsed['type'] && !isset($this->newCabinetData['_type_manually_set'])) {
                $this->newCabinetData['cabinet_type'] = $parsed['type'];
                $defaults = $this->typeDefaults[$parsed['type']] ?? [];
                $this->newCabinetData['depth_inches'] = $defaults['depth_inches'] ?? 24;
                $this->newCabinetData['height_inches'] = $defaults['height_inches'] ?? 34.5;
            }

            if ($parsed['width']) {
                $this->newCabinetData['length_inches'] = $parsed['width'];
            }
        }
    }

    /**
     * Parse cabinet name to extract type and dimensions
     * Examples: B24 → Base 24", W3012 → Wall 30"x12", SB36 → Sink Base 36"
     */
    protected function parseFromName(string $name): array
    {
        $name = strtoupper(trim($name));
        $result = ['type' => null, 'width' => null];

        $patterns = [
            // B24, DB24, SB36 - Base cabinets
            '/^(S?B|DB|BBC|LS|LZ)(\d+)$/' => ['type' => 'base', 'width_index' => 2],

            // W2430, W3012 - Wall cabinets (width x height)
            '/^(W|U)(\d{2})(\d{2})$/' => ['type' => 'wall', 'width_index' => 2],

            // W24, U30 - Simple wall
            '/^(W|U)(\d+)$/' => ['type' => 'wall', 'width_index' => 2],

            // T24, P24 - Tall/Pantry
            '/^(T|TP|P)(\d+)$/' => ['type' => 'tall', 'width_index' => 2],

            // V24, VD24 - Vanity
            '/^V(D)?(\d+)$/' => ['type' => 'vanity', 'width_index' => 2],
        ];

        foreach ($patterns as $pattern => $config) {
            if (preg_match($pattern, $name, $matches)) {
                $result['type'] = $config['type'];
                $result['width'] = (float) $matches[$config['width_index']];
                break;
            }
        }

        return $result;
    }

    /**
     * Parse various width input formats
     */
    protected function parseWidthInput(string $input): ?float
    {
        $input = trim(strtolower($input));

        // Remove common suffixes
        $input = preg_replace('/\s*(in|inch|inches|"|\'\'|ft|feet|foot)\s*$/', '', $input);

        // Handle feet input (2ft → 24)
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(?:ft|feet|foot|\')?$/', $input, $matches)) {
            return (float) $matches[1] * 12;
        }

        // Handle inches
        if (is_numeric($input)) {
            return (float) $input;
        }

        return null;
    }

    /**
     * Save the cabinet being added inline
     */
    public function saveCabinet(bool $addAnother = false): void
    {
        if (!$this->isAddingCabinet || !$this->addingToRunPath) {
            return;
        }

        // Validate minimum data - user must enter at least width or a cabinet code
        $userInput = trim($this->newCabinetData['name'] ?? '');
        $hasWidth = !empty($this->newCabinetData['length_inches']);

        if (empty($userInput) && !$hasWidth) {
            return;
        }

        Log::info('CabinetSpecBuilder: Saving inline cabinet', [
            'runPath' => $this->addingToRunPath,
            'data' => $this->newCabinetData,
            'addAnother' => $addAnother,
        ]);

        // Get run info for sequential naming
        $runNode = $this->getNodeByPath($this->addingToRunPath);
        $runType = $runNode['run_type'] ?? 'base';

        // Generate sequential name (B1, B2, W1, T1, etc.)
        $nextNum = $this->getNextCabinetCount($this->addingToRunPath);
        $prefix = $this->getRunPrefix($runType);
        $sequentialName = $prefix . $nextNum;

        // The user input becomes the cabinet code (DB18, SB36, etc.)
        // The sequential name (B1, W1) is auto-generated
        $cabinetCode = $userInput ?: null;

        // Create cabinet node with proper naming
        $newCabinet = array_merge($this->newCabinetData, [
            'id' => 'cabinet_' . Str::random(8),
            'type' => 'cabinet',
            'name' => $sequentialName, // Auto-generated: B1, B2, W1, etc.
            'code' => $cabinetCode, // User-entered: DB18, SB36, etc.
            'source' => 'user', // Track as user-created
            'created_at' => now()->toIso8601String(),
            'children' => [],
        ]);

        // Add to parent run
        $this->addChildToPath($this->addingToRunPath, $newCabinet);

        // Recalculate totals
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();

        if ($addAnother) {
            // Reset form for another entry
            $this->newCabinetData = [
                'name' => '', // User enters the code here
                'cabinet_type' => $runType === 'island' ? 'base' : $runType,
                'length_inches' => null,
                'depth_inches' => $this->typeDefaults[$runType]['depth_inches'] ?? 24,
                'height_inches' => $this->typeDefaults[$runType]['height_inches'] ?? 34.5,
                'quantity' => 1,
            ];
            $this->activeField = 'name';
        } else {
            $this->cancelAdd();
        }
    }

    /**
     * Cancel inline cabinet entry
     */
    public function cancelAdd(): void
    {
        $this->isAddingCabinet = false;
        $this->addingToRunPath = null;
        $this->newCabinetData = [];
        $this->activeField = '';
    }

    /**
     * Calculate live LF for new cabinet data
     */
    public function getNewCabinetLF(): float
    {
        $length = $this->newCabinetData['length_inches'] ?? 0;
        $qty = $this->newCabinetData['quantity'] ?? 1;

        if (!$length) {
            return 0;
        }

        return round(($length / 12) * $qty, 2);
    }

    // =========================================================================
    // AI ASSISTANT COMMAND HANDLERS
    // =========================================================================

    /**
     * Handle AI request to create a room
     */
    #[On('ai-create-room')]
    public function handleAiCreateRoom(array $data): void
    {
        Log::info('CabinetSpecBuilder: AI creating room', $data);

        $newRoom = [
            'id' => 'room_' . Str::random(8),
            'type' => 'room',
            'name' => $data['name'] ?? 'New Room',
            'room_type' => $data['room_type'] ?? 'kitchen',
            'floor_number' => $data['floor_number'] ?? 1,
            'source' => $data['source'] ?? 'ai', // Track creation source
            'created_at' => now()->toIso8601String(),
            'children' => [],
        ];

        $this->specData[] = $newRoom;
        $this->expanded[] = $newRoom['id'];
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Handle AI request to create a location in a room
     */
    #[On('ai-create-location')]
    public function handleAiCreateLocation(array $data): void
    {
        Log::info('CabinetSpecBuilder: AI creating location', $data);

        // Find room by name or path
        $roomPath = $data['room_path'] ?? $this->findRoomPathByName($data['room_name'] ?? null);

        if ($roomPath === null) {
            Log::warning('CabinetSpecBuilder: Room not found for AI location', $data);
            return;
        }

        $newLocation = [
            'id' => 'room_location_' . Str::random(8),
            'type' => 'room_location',
            'name' => $data['name'] ?? 'New Location',
            'location_type' => $data['location_type'] ?? 'wall',
            'cabinet_level' => (string) ($data['cabinet_level'] ?? '2'),
            'source' => $data['source'] ?? 'ai', // Track creation source
            'created_at' => now()->toIso8601String(),
            'children' => [],
        ];

        $this->addChildToPath($roomPath, $newLocation);
        $this->expanded[] = $newLocation['id'];
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Handle AI request to create a cabinet run
     */
    #[On('ai-create-run')]
    public function handleAiCreateRun(array $data): void
    {
        Log::info('CabinetSpecBuilder: AI creating run', $data);

        // Find location by path or by name
        $locationPath = $data['location_path'] ?? $this->findLocationPath($data);

        if ($locationPath === null) {
            Log::warning('CabinetSpecBuilder: Location not found for AI run', $data);
            return;
        }

        $newRun = [
            'id' => 'cabinet_run_' . Str::random(8),
            'type' => 'cabinet_run',
            'name' => $data['name'] ?? 'Cabinet Run',
            'run_type' => $data['run_type'] ?? 'base',
            'source' => $data['source'] ?? 'ai', // Track creation source
            'created_at' => now()->toIso8601String(),
            'children' => [],
        ];

        $this->addChildToPath($locationPath, $newRun);
        $this->expanded[] = $newRun['id'];
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Handle AI request to add cabinets to a run
     */
    #[On('ai-add-cabinet')]
    public function handleAiAddCabinet(array $data): void
    {
        Log::info('CabinetSpecBuilder: AI adding cabinets', $data);

        $cabinets = $data['cabinets'] ?? [$data];
        $source = $data['source'] ?? 'ai';

        foreach ($cabinets as $cabinetData) {
            // Find run by path or by name
            $runPath = $cabinetData['run_path'] ?? $this->findRunPath($cabinetData);

            if ($runPath === null) {
                // Try to find the most recent run
                $runPath = $this->findMostRecentRun($cabinetData['run_type'] ?? 'base');
            }

            if ($runPath === null) {
                Log::warning('CabinetSpecBuilder: Run not found for AI cabinet', $cabinetData);
                continue;
            }

            // Get run info for sequential naming
            $runNode = $this->getNodeByPath($runPath);
            $runType = $runNode['run_type'] ?? 'base';

            // Get the next sequential number for this run
            $nextNum = $this->getNextCabinetCount($runPath);
            $prefix = $this->getRunPrefix($runType);
            $sequentialName = $prefix . $nextNum;

            // Store original code (like DB18, SB36) separately from display name
            $originalCode = $cabinetData['name'] ?? 'Cabinet';

            $newCabinet = [
                'id' => 'cabinet_' . Str::random(8),
                'type' => 'cabinet',
                'name' => $sequentialName, // Sequential name (B1, B2, W1, etc.)
                'code' => $originalCode, // Original cabinet code (DB18, SB36, etc.)
                'cabinet_type' => $cabinetData['cabinet_type'] ?? 'base',
                'length_inches' => (float) ($cabinetData['length_inches'] ?? 24),
                'depth_inches' => (float) ($cabinetData['depth_inches'] ?? 24),
                'height_inches' => (float) ($cabinetData['height_inches'] ?? 34.5),
                'quantity' => (int) ($cabinetData['quantity'] ?? 1),
                'source' => $source, // Track creation source
                'created_at' => now()->toIso8601String(),
                'children' => [],
            ];

            $this->addChildToPath($runPath, $newCabinet);
        }

        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Get the next sequential count for cabinets in a run
     * (Counts existing cabinets + 1)
     */
    protected function getNextCabinetCount(string $runPath): int
    {
        $runNode = $this->getNodeByPath($runPath);
        if (!$runNode) {
            return 1;
        }

        $existingCabinets = $runNode['children'] ?? [];
        return count($existingCabinets) + 1;
    }

    /**
     * Get naming prefix based on run type
     * B = Base, W = Wall, T = Tall, I = Island
     */
    protected function getRunPrefix(string $runType): string
    {
        return match (strtolower($runType)) {
            'base' => 'B',
            'wall' => 'W',
            'tall' => 'T',
            'island' => 'I',
            default => 'C', // Generic cabinet
        };
    }

    /**
     * Handle AI request to delete an entity
     */
    #[On('ai-delete-entity')]
    public function handleAiDeleteEntity(array $data): void
    {
        Log::info('CabinetSpecBuilder: AI deleting entity', $data);

        $path = $data['path'] ?? null;

        if (!$path && isset($data['name'])) {
            // Try to find by name and type
            $path = $this->findEntityPathByName($data['name'], $data['type'] ?? null);
        }

        if ($path) {
            $this->deleteNodeByPath($path);
            $this->calculateTotals();
            $this->dispatchSpecDataUpdate();
        }
    }

    /**
     * Handle AI request to update pricing tier
     */
    #[On('ai-update-pricing')]
    public function handleAiUpdatePricing(array $data): void
    {
        Log::info('CabinetSpecBuilder: AI updating pricing', $data);

        $path = $data['path'] ?? null;

        if (!$path) {
            return;
        }

        $this->updateNodeByPath($path, [
            'cabinet_level' => (string) ($data['cabinet_level'] ?? '2'),
        ]);

        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();
    }

    // =========================================================================
    // INLINE EDITING METHODS (for Miller Columns UI)
    // =========================================================================

    /**
     * Update a single field on a cabinet (called from inline editing)
     */
    public function updateCabinetField(string $path, string $field, mixed $value): void
    {
        Log::info('CabinetSpecBuilder: Updating cabinet field', [
            'path' => $path,
            'field' => $field,
            'value' => $value,
        ]);

        $node = $this->getNodeByPath($path);

        if (!$node || ($node['type'] ?? '') !== 'cabinet') {
            Log::warning('CabinetSpecBuilder: Invalid path for cabinet field update', ['path' => $path]);
            return;
        }

        // Validate and cast numeric fields
        if (in_array($field, ['length_inches', 'height_inches', 'depth_inches'])) {
            $value = (float) $value;
            if ($value <= 0) {
                return; // Don't save invalid dimensions
            }
        } elseif ($field === 'quantity') {
            $value = max(1, (int) $value);
        }

        // Update the specific field
        $this->updateNodeByPath($path, [$field => $value]);

        // Recalculate totals (LF changes when dimensions change)
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Delete a cabinet by its path (called from inline editing)
     */
    public function deleteCabinetByPath(string $path): void
    {
        Log::info('CabinetSpecBuilder: Deleting cabinet by path', ['path' => $path]);

        $node = $this->getNodeByPath($path);

        if (!$node) {
            Log::warning('CabinetSpecBuilder: Node not found for deletion', ['path' => $path]);
            return;
        }

        $this->deleteNodeByPath($path);
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Find room path by name
     */
    protected function findRoomPathByName(?string $name): ?string
    {
        if (!$name) {
            // Return first room if exists
            return !empty($this->specData) ? '0' : null;
        }

        $nameLower = strtolower($name);

        foreach ($this->specData as $index => $room) {
            $roomName = strtolower($room['name'] ?? '');
            if ($roomName === $nameLower || str_contains($roomName, $nameLower) || str_contains($nameLower, $roomName)) {
                return (string) $index;
            }
        }

        return null;
    }

    /**
     * Find location path by criteria
     */
    protected function findLocationPath(array $criteria): ?string
    {
        $roomPath = $this->findRoomPathByName($criteria['room_name'] ?? null);

        if ($roomPath === null) {
            return null;
        }

        $roomIndex = (int) $roomPath;
        $room = $this->specData[$roomIndex] ?? null;

        if (!$room || empty($room['children'])) {
            return null;
        }

        $locationName = strtolower($criteria['location_name'] ?? '');

        foreach ($room['children'] as $locIndex => $location) {
            $locName = strtolower($location['name'] ?? '');

            if ($locationName && ($locName === $locationName || str_contains($locName, $locationName))) {
                return "{$roomIndex}.children.{$locIndex}";
            }
        }

        // Return first location if no name match
        return "{$roomIndex}.children.0";
    }

    /**
     * Find run path by criteria
     */
    protected function findRunPath(array $criteria): ?string
    {
        $locationPath = $this->findLocationPath($criteria);

        if ($locationPath === null) {
            return null;
        }

        $location = $this->getNodeByPath($locationPath);

        if (!$location || empty($location['children'])) {
            return null;
        }

        $runName = strtolower($criteria['run_name'] ?? '');
        $runType = $criteria['run_type'] ?? null;

        foreach ($location['children'] as $runIndex => $run) {
            $rName = strtolower($run['name'] ?? '');
            $rType = $run['run_type'] ?? null;

            // Match by name
            if ($runName && ($rName === $runName || str_contains($rName, $runName))) {
                return "{$locationPath}.children.{$runIndex}";
            }

            // Match by type
            if ($runType && $rType === $runType) {
                return "{$locationPath}.children.{$runIndex}";
            }
        }

        // Return first run if no match
        return "{$locationPath}.children.0";
    }

    /**
     * Find the most recent run of a given type (for quick cabinet adding)
     */
    protected function findMostRecentRun(?string $runType = null): ?string
    {
        // Search from bottom up (most recent rooms/locations first)
        for ($roomIdx = count($this->specData) - 1; $roomIdx >= 0; $roomIdx--) {
            $room = $this->specData[$roomIdx];

            if (empty($room['children'])) {
                continue;
            }

            for ($locIdx = count($room['children']) - 1; $locIdx >= 0; $locIdx--) {
                $location = $room['children'][$locIdx];

                if (empty($location['children'])) {
                    continue;
                }

                for ($runIdx = count($location['children']) - 1; $runIdx >= 0; $runIdx--) {
                    $run = $location['children'][$runIdx];

                    if ($runType === null || ($run['run_type'] ?? '') === $runType) {
                        return "{$roomIdx}.children.{$locIdx}.children.{$runIdx}";
                    }
                }
            }
        }

        return null;
    }

    /**
     * Find entity path by name and optional type
     */
    protected function findEntityPathByName(string $name, ?string $type = null): ?string
    {
        $nameLower = strtolower($name);

        // Search through hierarchy
        foreach ($this->specData as $roomIdx => $room) {
            if (strtolower($room['name'] ?? '') === $nameLower) {
                if (!$type || $type === 'room') {
                    return (string) $roomIdx;
                }
            }

            foreach ($room['children'] ?? [] as $locIdx => $location) {
                if (strtolower($location['name'] ?? '') === $nameLower) {
                    if (!$type || $type === 'room_location' || $type === 'location') {
                        return "{$roomIdx}.children.{$locIdx}";
                    }
                }

                foreach ($location['children'] ?? [] as $runIdx => $run) {
                    if (strtolower($run['name'] ?? '') === $nameLower) {
                        if (!$type || $type === 'cabinet_run' || $type === 'run') {
                            return "{$roomIdx}.children.{$locIdx}.children.{$runIdx}";
                        }
                    }

                    foreach ($run['children'] ?? [] as $cabIdx => $cabinet) {
                        if (strtolower($cabinet['name'] ?? '') === $nameLower) {
                            if (!$type || $type === 'cabinet') {
                                return "{$roomIdx}.children.{$locIdx}.children.{$runIdx}.children.{$cabIdx}";
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    public function render()
    {
        return view('webkul-project::livewire.cabinet-spec-builder');
    }
}
