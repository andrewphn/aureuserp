<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Services\TcsPricingService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Notifications\Notification;

/**
 * Cabinet Spec Builder
 *
 * A Notion-like hierarchical tree builder for cabinet specifications.
 * Hierarchy: Room → RoomLocation → CabinetRun → Cabinet
 *
 * Data is stored as JSON in draft, entities created on project save.
 */
class CabinetSpecBuilder extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    // Tree data structure
    public array $specData = [];
    public array $expanded = [];

    // Calculated totals
    public float $totalLinearFeet = 0;
    public float $totalPrice = 0;

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
        'section' => 'Section',
        'content' => 'Content',
        'hardware' => 'Hardware',
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

    // Pricing options loaded from TcsPricingService
    public array $pricingTiers = [];
    public array $materialOptions = [];
    public array $finishOptions = [];

    // Pricing service instance
    protected ?TcsPricingService $pricingService = null;

    // Content types (what goes inside a section/opening)
    public array $contentTypes = [
        'door' => 'Door',
        'drawer' => 'Drawer',
        'shelf' => 'Shelf',
        'pullout' => 'Pull-out',
        'panel' => 'Panel',
        'appliance' => 'Appliance Opening',
        'divider' => 'Divider',
    ];

    // Hardware component types
    public array $hardwareTypes = [
        'hinge' => 'Hinge',
        'slide' => 'Drawer Slide',
        'handle' => 'Handle/Pull',
        'knob' => 'Knob',
        'soft_close' => 'Soft Close',
        'shelf_pin' => 'Shelf Pin',
        'bracket' => 'Bracket',
        'other' => 'Other',
    ];

    // Selected cabinet for inspector detail view
    public ?int $selectedCabinetIndex = null;
    public ?array $selectedCabinet = null;

    public function mount(array $specData = []): void
    {
        if (!empty($specData)) {
            $this->specData = $specData;
        }

        // Initialize pricing service and load options from database
        $this->pricingService = app(TcsPricingService::class);
        $this->loadPricingOptions();

        $this->calculateTotals();
    }

    /**
     * Load pricing options from TcsPricingService
     */
    protected function loadPricingOptions(): void
    {
        if (!$this->pricingService) {
            $this->pricingService = app(TcsPricingService::class);
        }

        $this->pricingTiers = $this->pricingService->getCabinetLevelOptions();
        $this->materialOptions = $this->pricingService->getMaterialCategoryOptions();
        $this->finishOptions = $this->pricingService->getFinishOptions();
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
     * Get default form data for a new entity
     */
    protected function getDefaultFormData(string $type): array
    {
        return match ($type) {
            'room' => [
                'name' => '',
                'room_type' => 'kitchen',
                'floor_number' => 1,
                'cabinet_level' => '3',
                'material_category' => 'stain_grade',
                'finish_option' => 'unfinished',
            ],
            'room_location' => [
                'name' => '',
                'location_type' => 'wall',
                'cabinet_level' => null, // Inherit from room
                'material_category' => null, // Inherit from room
                'finish_option' => null, // Inherit from room
            ],
            'cabinet_run' => [
                'name' => '',
                'run_type' => 'base',
                'cabinet_level' => null, // Inherit from location
                'material_category' => null, // Inherit from location
                'finish_option' => null, // Inherit from location
            ],
            'cabinet' => [
                'name' => '',
                'cabinet_type' => 'base',
                'length_inches' => 24,
                'depth_inches' => 24,
                'height_inches' => 30,
                'quantity' => 1,
            ],
            'section' => [
                'name' => '',
                'width_inches' => null,
                'height_inches' => null,
                'depth_inches' => null,
            ],
            'content' => [
                'name' => '',
                'type' => 'drawer',
                'width_inches' => null,
                'height_inches' => null,
                'depth_inches' => null,
                'quantity' => 1,
            ],
            'hardware' => [
                'component_type' => 'slide',
                'product_id' => null,
                'name' => '',
                'sku' => '',
                'unit_cost' => null,
                'quantity' => 1,
            ],
            default => ['name' => ''],
        };
    }

    /**
     * Calculate totals (rollup from bottom to top)
     * Uses TcsPricingService for proper pricing with level + material + finish
     */
    public function calculateTotals(): void
    {
        foreach ($this->specData as &$room) {
            $room['linear_feet'] = 0;
            $room['estimated_price'] = 0;

            // Room-level defaults (used for inheritance)
            $roomCabinetLevel = $room['cabinet_level'] ?? '3';
            $roomMaterialCategory = $room['material_category'] ?? 'stain_grade';
            $roomFinishOption = $room['finish_option'] ?? 'unfinished';

            foreach ($room['children'] ?? [] as &$location) {
                $location['linear_feet'] = 0;
                $location['estimated_price'] = 0;

                // Location inherits from room if not set
                $locationCabinetLevel = $location['cabinet_level'] ?? $roomCabinetLevel;
                $locationMaterialCategory = $location['material_category'] ?? $roomMaterialCategory;
                $locationFinishOption = $location['finish_option'] ?? $roomFinishOption;

                foreach ($location['children'] ?? [] as &$run) {
                    $run['linear_feet'] = 0;

                    // Run inherits from location if not set
                    $runCabinetLevel = $run['cabinet_level'] ?? $locationCabinetLevel;
                    $runMaterialCategory = $run['material_category'] ?? $locationMaterialCategory;
                    $runFinishOption = $run['finish_option'] ?? $locationFinishOption;

                    // Sum cabinets in this run
                    foreach ($run['children'] ?? [] as $cabinet) {
                        $cabinetLF = ($cabinet['length_inches'] ?? 0) / 12 * ($cabinet['quantity'] ?? 1);
                        $run['linear_feet'] += $cabinetLF;
                    }

                    // Calculate run price using inherited or explicit pricing
                    $run['estimated_price'] = $run['linear_feet'] * $this->getPricePerLF(
                        $runCabinetLevel,
                        $runMaterialCategory,
                        $runFinishOption
                    );

                    // Roll up to location
                    $location['linear_feet'] += $run['linear_feet'];
                    $location['estimated_price'] += $run['estimated_price'];
                }

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
     * Get price per linear foot using TcsPricingService
     *
     * @param string $cabinetLevel Cabinet level (1-5)
     * @param string $materialCategory Material category slug
     * @param string $finishOption Finish option slug
     * @return float Total price per LF
     */
    protected function getPricePerLF(
        string $cabinetLevel,
        string $materialCategory = 'stain_grade',
        string $finishOption = 'unfinished'
    ): float {
        if (!$this->pricingService) {
            $this->pricingService = app(TcsPricingService::class);
        }

        return $this->pricingService->calculateUnitPrice(
            $cabinetLevel,
            $materialCategory,
            $finishOption
        );
    }

    /**
     * Get price breakdown for display in UI
     */
    public function getPriceBreakdown(string $cabinetLevel, string $materialCategory, string $finishOption): array
    {
        if (!$this->pricingService) {
            $this->pricingService = app(TcsPricingService::class);
        }

        return $this->pricingService->getPriceBreakdown($cabinetLevel, $materialCategory, $finishOption);
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
     * Get child type for a given entity type
     */
    public function getChildType(string $type): ?string
    {
        return match ($type) {
            'room' => 'room_location',
            'room_location' => 'cabinet_run',
            'cabinet_run' => 'cabinet',
            'cabinet' => 'section',
            'section' => 'content',
            'content' => 'hardware',
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
            'cabinet' => 'Section',
            'section' => 'Content',
            'content' => 'Hardware',
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
     * Update any node by path (public method for JavaScript calls)
     * Used for inline editing of pricing fields, etc.
     */
    public function updateNodeField(string $path, array $data): void
    {
        Log::info('CabinetSpecBuilder: Updating node field', [
            'path' => $path,
            'data' => $data,
        ]);

        $node = $this->getNodeByPath($path);

        if (!$node) {
            Log::warning('CabinetSpecBuilder: Node not found for update', ['path' => $path]);
            return;
        }

        $this->updateNodeByPath($path, $data);
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

    // =========================================================================
    // CABINET DETAIL METHODS (sections, contents, hardware)
    // =========================================================================

    /**
     * Select a cabinet to show its sections/contents/hardware in the inspector
     */
    public function selectCabinet(int $cabIndex): void
    {
        $this->selectedCabinetIndex = $cabIndex;
    }

    /**
     * Clear cabinet selection
     */
    public function clearCabinetSelection(): void
    {
        $this->selectedCabinetIndex = null;
        $this->selectedCabinet = null;
    }

    /**
     * Add a section to a cabinet
     */
    public function addSection(string $cabinetPath): void
    {
        $cabinet = $this->getNodeByPath($cabinetPath);
        if (!$cabinet) return;

        $sectionCount = count($cabinet['children'] ?? []) + 1;

        $newSection = [
            'id' => 'section_' . \Illuminate\Support\Str::random(8),
            'type' => 'section',
            'name' => "Opening {$sectionCount}",
            'width_inches' => $cabinet['length_inches'] ?? null,
            'height_inches' => $cabinet['height_inches'] ?? null,
            'depth_inches' => $cabinet['depth_inches'] ?? null,
            'source' => 'user',
            'created_at' => now()->toIso8601String(),
            'children' => [],
        ];

        $this->addChildToPath($cabinetPath, $newSection);
        $this->expanded[] = $newSection['id'];
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Add content (drawer, door, shelf, etc.) to a section
     */
    public function addContent(string $sectionPath, string $contentType = 'drawer'): void
    {
        $section = $this->getNodeByPath($sectionPath);
        if (!$section) return;

        $contentCount = count($section['children'] ?? []) + 1;
        $typeName = ucfirst($contentType);

        // Get default dimensions based on content type
        $defaults = $this->getContentDefaults($contentType);

        $newContent = [
            'id' => 'content_' . \Illuminate\Support\Str::random(8),
            'type' => 'content',
            'content_type' => $contentType,
            'name' => "{$typeName} {$contentCount}",
            'width_inches' => $section['width_inches'] ?? null,
            'height_inches' => $defaults['height_inches'] ?? null,
            'depth_inches' => $defaults['depth_inches'] ?? null,
            'quantity' => 1,
            'source' => 'user',
            'created_at' => now()->toIso8601String(),
            'children' => [],
        ];

        $this->addChildToPath($sectionPath, $newContent);
        $this->expanded[] = $newContent['id'];
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Get default dimensions for a content type
     */
    protected function getContentDefaults(string $contentType): array
    {
        return match ($contentType) {
            'drawer' => ['height_inches' => 6, 'depth_inches' => null], // Depth set by slide
            'door' => ['height_inches' => null, 'depth_inches' => 0.75],
            'shelf' => ['height_inches' => 0.75, 'depth_inches' => null],
            'pullout' => ['height_inches' => 4, 'depth_inches' => null],
            'panel' => ['height_inches' => null, 'depth_inches' => 0.75],
            default => ['height_inches' => null, 'depth_inches' => null],
        };
    }

    /**
     * Add hardware to content
     */
    public function addHardware(string $contentPath, string $hardwareType = 'slide'): void
    {
        $content = $this->getNodeByPath($contentPath);
        if (!$content) return;

        $hwCount = count($content['children'] ?? []) + 1;
        $typeName = ucfirst(str_replace('_', ' ', $hardwareType));

        $newHardware = [
            'id' => 'hardware_' . \Illuminate\Support\Str::random(8),
            'type' => 'hardware',
            'component_type' => $hardwareType,
            'name' => "{$typeName} {$hwCount}",
            'product_id' => null,
            'sku' => null,
            'unit_cost' => null,
            'quantity' => $this->getDefaultHardwareQty($hardwareType, $content),
            'source' => 'user',
            'created_at' => now()->toIso8601String(),
            'children' => [],
        ];

        $this->addChildToPath($contentPath, $newHardware);
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Get default quantity for hardware based on type and content
     */
    protected function getDefaultHardwareQty(string $hardwareType, array $content): int
    {
        return match ($hardwareType) {
            'slide' => 1, // 1 pair = 1 unit
            'hinge' => 2, // Typically 2 hinges per door
            'handle', 'knob' => 1,
            'shelf_pin' => 4, // 4 pins per shelf
            default => 1,
        };
    }

    /**
     * Update hardware with product selection and auto-calculate dimensions
     */
    public function updateHardwareProduct(string $hardwarePath, int $productId): void
    {
        $hardware = $this->getNodeByPath($hardwarePath);
        if (!$hardware) return;

        $product = \Webkul\Product\Models\Product::find($productId);
        if (!$product) return;

        // Update hardware with product info
        $this->updateNodeByPath($hardwarePath, [
            'product_id' => $productId,
            'name' => $product->name,
            'sku' => $product->reference,
            'unit_cost' => $product->cost,
        ]);

        // Auto-calculate drawer dimensions if this is a slide and parent is drawer
        $componentType = $hardware['component_type'] ?? '';
        if ($componentType === 'slide') {
            $this->autoCalculateDrawerDimensions($hardwarePath, $product);
        }

        $this->dispatchSpecDataUpdate();
    }

    /**
     * Auto-calculate drawer box dimensions based on section opening AND slide specs
     * - Section opening provides the base/max dimensions
     * - Slide specs adjust for clearance and may constrain depth
     */
    protected function autoCalculateDrawerDimensions(string $hardwarePath, $product): void
    {
        // Navigate up to find the content (drawer) and section (opening)
        // Hardware path: roomIdx.children.locIdx.children.runIdx.children.cabIdx.children.secIdx.children.contIdx.children.hwIdx
        $pathParts = explode('.', $hardwarePath);

        // Content is 2 levels up from hardware (remove .children.hwIdx)
        $contentPath = implode('.', array_slice($pathParts, 0, -2));
        $content = $this->getNodeByPath($contentPath);

        // Section is 2 more levels up from content
        $sectionPath = implode('.', array_slice($pathParts, 0, -4));
        $section = $this->getNodeByPath($sectionPath);

        if (!$content || !$section) {
            \Illuminate\Support\Facades\Log::info('CabinetSpecBuilder: Could not find content/section for auto-calculate', [
                'hardwarePath' => $hardwarePath,
                'contentPath' => $contentPath,
                'sectionPath' => $sectionPath,
            ]);
            return;
        }

        $contentType = $content['content_type'] ?? $content['type'] ?? '';
        if ($contentType !== 'drawer' && $contentType !== 'content') {
            return;
        }

        // Get opening dimensions from section - these are the BASE dimensions
        $openingWidth = (float) ($section['width_inches'] ?? 0);
        $openingDepth = (float) ($section['depth_inches'] ?? 0);

        // Get slide specifications - these ADJUST the dimensions
        $specs = $product->getNumericSpecifications();

        // Default side clearance (1" total = 0.5" per side)
        $sideClearance = 1.0;
        $slideLengthInches = null;
        $depthOffset = 0.394; // Default 10mm offset

        if (!$specs->isEmpty()) {
            // Get clearance from slide specs
            $clearanceSpec = $specs->get('Total Width Clearance');
            if ($clearanceSpec) {
                $clearanceValue = $clearanceSpec['value'];
                $clearanceUnit = $clearanceSpec['unit'] ?? 'mm';
                $sideClearance = ($clearanceUnit === 'mm')
                    ? $clearanceValue / 25.4
                    : $clearanceValue;
            }

            // Get slide length
            $slideLengthSpec = $specs->get('Slide Length');
            if ($slideLengthSpec) {
                $slideLength = $slideLengthSpec['value'];
                $slideLengthUnit = $slideLengthSpec['unit'] ?? 'in';
                $slideLengthInches = ($slideLengthUnit === 'mm')
                    ? $slideLength / 25.4
                    : $slideLength;
            }

            // Get depth offset
            $depthOffsetSpec = $specs->get('Depth Offset');
            if ($depthOffsetSpec) {
                $depthOffsetValue = $depthOffsetSpec['value'];
                $depthOffsetUnit = $depthOffsetSpec['unit'] ?? 'mm';
                $depthOffset = ($depthOffsetUnit === 'mm')
                    ? $depthOffsetValue / 25.4
                    : $depthOffsetValue;
            }
        }

        // Calculate drawer width: section opening width - slide clearance
        if ($openingWidth > 0) {
            $drawerWidth = $openingWidth - $sideClearance;
            if ($drawerWidth > 0) {
                $this->updateNodeByPath($contentPath, [
                    'width_inches' => round($drawerWidth, 4),
                ]);

                \Illuminate\Support\Facades\Log::info('CabinetSpecBuilder: Auto-calculated drawer width', [
                    'openingWidth' => $openingWidth,
                    'slideClearance' => $sideClearance,
                    'drawerWidth' => $drawerWidth,
                ]);
            }
        }

        // Calculate drawer depth:
        // - Start with section opening depth as the base
        // - If slide length is available, use slide-based depth as a constraint
        $drawerDepth = null;

        if ($openingDepth > 0) {
            // Section depth is the base
            $drawerDepth = $openingDepth;
        }

        if ($slideLengthInches !== null) {
            // Slide-based depth: slide length - offset
            $slideBasedDepth = $slideLengthInches - $depthOffset;

            if ($drawerDepth === null) {
                // No section depth set, use slide-based
                $drawerDepth = $slideBasedDepth;
            } else {
                // Use the smaller of section depth or slide-based depth
                $drawerDepth = min($drawerDepth, $slideBasedDepth);
            }
        }

        if ($drawerDepth !== null && $drawerDepth > 0) {
            $this->updateNodeByPath($contentPath, [
                'depth_inches' => round($drawerDepth, 4),
            ]);

            \Illuminate\Support\Facades\Log::info('CabinetSpecBuilder: Auto-calculated drawer depth', [
                'openingDepth' => $openingDepth,
                'slideLength' => $slideLengthInches,
                'depthOffset' => $depthOffset,
                'drawerDepth' => $drawerDepth,
            ]);
        }
    }

    /**
     * Search products by component type
     */
    public function searchProducts(string $search, string $componentType): array
    {
        $searchTerms = match ($componentType) {
            'hinge' => ['hinge'],
            'slide' => ['slide', 'drawer slide', 'legrabox'],
            'handle' => ['handle', 'pull'],
            'knob' => ['knob'],
            'soft_close' => ['soft close', 'blumotion'],
            'shelf_pin' => ['shelf pin', 'shelf support'],
            'bracket' => ['bracket'],
            default => [],
        };

        $query = \Webkul\Product\Models\Product::query()
            ->where(function ($q) use ($search, $searchTerms) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");

                foreach ($searchTerms as $term) {
                    $q->orWhere('name', 'like', "%{$term}%");
                }
            })
            ->limit(20);

        return $query->get()
            ->mapWithKeys(fn ($product) => [
                $product->id => "{$product->name} ({$product->reference})"
            ])
            ->toArray();
    }

    /**
     * Update a section field inline
     */
    public function updateSectionField(string $path, string $field, mixed $value): void
    {
        $node = $this->getNodeByPath($path);
        if (!$node || ($node['type'] ?? '') !== 'section') return;

        if (in_array($field, ['width_inches', 'height_inches', 'depth_inches'])) {
            $value = (float) $value;
            if ($value <= 0) return;
        }

        $this->updateNodeByPath($path, [$field => $value]);
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Update a content field inline
     */
    public function updateContentField(string $path, string $field, mixed $value): void
    {
        $node = $this->getNodeByPath($path);
        if (!$node || ($node['type'] ?? '') !== 'content') return;

        if (in_array($field, ['width_inches', 'height_inches', 'depth_inches'])) {
            $value = (float) $value;
            if ($value <= 0) return;
        } elseif ($field === 'quantity') {
            $value = max(1, (int) $value);
        }

        $this->updateNodeByPath($path, [$field => $value]);
        $this->dispatchSpecDataUpdate();
    }

    /**
     * Update a hardware field inline
     */
    public function updateHardwareField(string $path, string $field, mixed $value): void
    {
        $node = $this->getNodeByPath($path);
        if (!$node || ($node['type'] ?? '') !== 'hardware') return;

        if ($field === 'quantity') {
            $value = max(1, (int) $value);
        } elseif ($field === 'unit_cost') {
            $value = max(0, (float) $value);
        }

        $this->updateNodeByPath($path, [$field => $value]);
        $this->dispatchSpecDataUpdate();
    }

    // =========================================================================
    // FILAMENT ACTIONS
    // =========================================================================

    /**
     * Filament Action: Create a new Room
     */
    public function createRoomAction(): Action
    {
        return Action::make('createRoom')
            ->label('Add Room')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->modalWidth('sm')  // Small modal - only 3 fields needed
            ->closeModalByClickingAway()
            ->form([
                Section::make('Room Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Room Name')
                            ->required()
                            ->placeholder('e.g., Kitchen, Master Bath')
                            ->autofocus(),
                        Select::make('room_type')
                            ->label('Room Type')
                            ->options($this->roomTypes)
                            ->required()
                            ->default('kitchen'),
                        TextInput::make('floor_number')
                            ->label('Floor Number')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->maxValue(99),
                    ]),
                Section::make('Default Pricing')
                    ->description('These defaults will apply to all cabinets in this room unless overridden.')
                    ->schema([
                        Select::make('cabinet_level')
                            ->label('Cabinet Level')
                            ->options($this->pricingTiers)
                            ->default('3'),
                        Select::make('material_category')
                            ->label('Material Category')
                            ->options($this->materialOptions)
                            ->default('stain_grade'),
                        Select::make('finish_option')
                            ->label('Finish Option')
                            ->options($this->finishOptions)
                            ->default('unfinished'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->action(function (array $data): void {
                $this->createRoomFromAction($data);
            });
    }

    /**
     * Create room from Filament Action data
     */
    protected function createRoomFromAction(array $data): void
    {
        $newRoom = [
            'id' => 'room_' . Str::random(8),
            'type' => 'room',
            'source' => 'user',
            'created_at' => now()->toIso8601String(),
            'children' => [],
            'name' => $data['name'],
            'room_type' => $data['room_type'] ?? 'kitchen',
            'floor_number' => $data['floor_number'] ?? 1,
            'cabinet_level' => $data['cabinet_level'] ?? '3',
            'material_category' => $data['material_category'] ?? 'stain_grade',
            'finish_option' => $data['finish_option'] ?? 'unfinished',
        ];

        $this->specData[] = $newRoom;
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();

        // Auto-select the new room
        $this->dispatch('selectRoom', roomIdx: count($this->specData) - 1);

        Notification::make()
            ->success()
            ->title('Room Added')
            ->body("\"{$data['name']}\" has been created. Add locations to continue.")
            ->duration(3000)
            ->send();

        Log::info('CabinetSpecBuilder: Room created via Filament Action', [
            'room' => $newRoom,
        ]);
    }

    /**
     * Filament Action: Create a new Location within a Room
     */
    public function createLocationAction(): Action
    {
        return Action::make('createLocation')
            ->label('Add Location')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->modalWidth('sm')  // Small modal - only 2 required fields
            ->closeModalByClickingAway()
            ->form([
                Section::make('Location Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Location Name')
                            ->required()
                            ->placeholder('e.g., North Wall, Island')
                            ->autofocus(),
                        Select::make('location_type')
                            ->label('Location Type')
                            ->options($this->locationTypes)
                            ->required()
                            ->default('wall'),
                    ]),
                Section::make('Pricing Override')
                    ->description('Leave blank to inherit from room defaults.')
                    ->schema([
                        Select::make('cabinet_level')
                            ->label('Cabinet Level')
                            ->options($this->pricingTiers)
                            ->placeholder('Inherit from room'),
                        Select::make('material_category')
                            ->label('Material Category')
                            ->options($this->materialOptions)
                            ->placeholder('Inherit from room'),
                        Select::make('finish_option')
                            ->label('Finish Option')
                            ->options($this->finishOptions)
                            ->placeholder('Inherit from room'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->action(function (array $data, array $arguments): void {
                $roomPath = $arguments['roomPath'] ?? null;
                if ($roomPath !== null) {
                    $this->createLocationFromAction($data, $roomPath);
                }
            });
    }

    /**
     * Create location from Filament Action data
     */
    protected function createLocationFromAction(array $data, string $roomPath): void
    {
        $newLocation = [
            'id' => 'room_location_' . Str::random(8),
            'type' => 'room_location',
            'source' => 'user',
            'created_at' => now()->toIso8601String(),
            'children' => [],
            'name' => $data['name'],
            'location_type' => $data['location_type'] ?? 'wall',
            'cabinet_level' => $data['cabinet_level'] ?? null,
            'material_category' => $data['material_category'] ?? null,
            'finish_option' => $data['finish_option'] ?? null,
        ];

        $this->addChildToPath($roomPath, $newLocation);
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();

        Notification::make()
            ->success()
            ->title('Location Added')
            ->body("\"{$data['name']}\" has been created. Add cabinet runs to continue.")
            ->duration(3000)
            ->send();

        Log::info('CabinetSpecBuilder: Location created via Filament Action', [
            'location' => $newLocation,
            'roomPath' => $roomPath,
        ]);
    }

    /**
     * Filament Action: Create a new Cabinet Run within a Location
     */
    public function createRunAction(): Action
    {
        return Action::make('createRun')
            ->label('Add Run')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->modalWidth('sm')  // Small modal - only 2 required fields
            ->closeModalByClickingAway()
            ->form([
                Section::make('Run Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Run Name')
                            ->required()
                            ->placeholder('e.g., Base Run, Upper Run')
                            ->autofocus(),
                        Select::make('run_type')
                            ->label('Run Type')
                            ->options($this->runTypes)
                            ->required()
                            ->default('base'),
                    ]),
                Section::make('Pricing Override')
                    ->description('Leave blank to inherit from location/room defaults.')
                    ->schema([
                        Select::make('cabinet_level')
                            ->label('Cabinet Level')
                            ->options($this->pricingTiers)
                            ->placeholder('Inherit from parent'),
                        Select::make('material_category')
                            ->label('Material Category')
                            ->options($this->materialOptions)
                            ->placeholder('Inherit from parent'),
                        Select::make('finish_option')
                            ->label('Finish Option')
                            ->options($this->finishOptions)
                            ->placeholder('Inherit from parent'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->action(function (array $data, array $arguments): void {
                $locationPath = $arguments['locationPath'] ?? null;
                if ($locationPath !== null) {
                    $this->createRunFromAction($data, $locationPath);
                }
            });
    }

    /**
     * Create run from Filament Action data
     */
    protected function createRunFromAction(array $data, string $locationPath): void
    {
        $newRun = [
            'id' => 'cabinet_run_' . Str::random(8),
            'type' => 'cabinet_run',
            'source' => 'user',
            'created_at' => now()->toIso8601String(),
            'children' => [],
            'name' => $data['name'],
            'run_type' => $data['run_type'] ?? 'base',
            'cabinet_level' => $data['cabinet_level'] ?? null,
            'material_category' => $data['material_category'] ?? null,
            'finish_option' => $data['finish_option'] ?? null,
        ];

        $this->addChildToPath($locationPath, $newRun);
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();

        Notification::make()
            ->success()
            ->title('Run Added')
            ->body("\"{$data['name']}\" ready for cabinets. Use quick-add: B24, W30, SB36...")
            ->duration(4000)
            ->send();

        Log::info('CabinetSpecBuilder: Run created via Filament Action', [
            'run' => $newRun,
            'locationPath' => $locationPath,
        ]);
    }

    /**
     * Filament Action: Create a new cabinet within a run
     */
    public function createCabinetAction(): Action
    {
        return Action::make('createCabinet')
            ->label('Add Cabinet')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->modalWidth('md')
            ->form([
                Section::make('Cabinet Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Cabinet Code')
                                    ->placeholder('e.g., B24, W3012, SB36')
                                    ->helperText('Enter code like B24 to auto-fill type and width')
                                    ->autofocus()
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $parsed = $this->parseFromName($state);
                                            if ($parsed['type']) {
                                                $set('cabinet_type', $parsed['type']);
                                                $defaults = $this->typeDefaults[$parsed['type']] ?? [];
                                                $set('depth_inches', $defaults['depth_inches'] ?? 24);
                                                $set('height_inches', $defaults['height_inches'] ?? 34.5);
                                            }
                                            if ($parsed['width']) {
                                                $set('length_inches', $parsed['width']);
                                            }
                                        }
                                    }),
                                Select::make('cabinet_type')
                                    ->label('Cabinet Type')
                                    ->options($this->cabinetTypes)
                                    ->required()
                                    ->default('base'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('length_inches')
                                    ->label('Width (in)')
                                    ->numeric()
                                    ->required()
                                    ->default(24)
                                    ->minValue(6)
                                    ->maxValue(96),
                                TextInput::make('depth_inches')
                                    ->label('Depth (in)')
                                    ->numeric()
                                    ->required()
                                    ->default(24)
                                    ->minValue(6)
                                    ->maxValue(36),
                                TextInput::make('height_inches')
                                    ->label('Height (in)')
                                    ->numeric()
                                    ->required()
                                    ->default(34.5)
                                    ->minValue(6)
                                    ->maxValue(96),
                            ]),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(50),
                    ]),
            ])
            ->action(function (array $data, array $arguments): void {
                $runPath = $arguments['runPath'] ?? null;
                if ($runPath !== null) {
                    $this->createCabinetFromAction($data, $runPath);
                }
            });
    }

    /**
     * Create cabinet from Filament Action data
     */
    protected function createCabinetFromAction(array $data, string $runPath): void
    {
        $runNode = $this->getNodeByPath($runPath);
        $runType = $runNode['run_type'] ?? 'base';

        // Get sequential name (B1, B2, W1, etc.)
        $sequentialName = $this->getNextCabinetNumber($runPath, $runType);

        $newCabinet = [
            'id' => 'cabinet_' . Str::random(8),
            'type' => 'cabinet',
            'source' => 'user',
            'created_at' => now()->toIso8601String(),
            'name' => $sequentialName,
            'code' => $data['code'] ?? null,
            'cabinet_type' => $data['cabinet_type'] ?? 'base',
            'length_inches' => $data['length_inches'] ?? 24,
            'depth_inches' => $data['depth_inches'] ?? 24,
            'height_inches' => $data['height_inches'] ?? 34.5,
            'quantity' => $data['quantity'] ?? 1,
            'children' => [],
        ];

        $this->addChildToPath($runPath, $newCabinet);
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();

        $width = $data['length_inches'] ?? 24;
        Notification::make()
            ->success()
            ->title("Cabinet {$sequentialName} Added")
            ->body("{$width}\" wide cabinet added to run.")
            ->duration(2000)
            ->send();

        Log::info('CabinetSpecBuilder: Cabinet created via Filament Action', [
            'cabinet' => $newCabinet,
            'runPath' => $runPath,
        ]);
    }

    /**
     * Filament Action: Edit any entity node
     */
    public function editNodeAction(): Action
    {
        return Action::make('editNode')
            ->label('Edit')
            ->icon('heroicon-m-pencil')
            ->color('gray')
            ->slideOver()  // SlideOver for detailed editing - can see tree while editing
            ->fillForm(function (array $arguments): array {
                $path = $arguments['nodePath'] ?? null;
                if ($path) {
                    $node = $this->getNodeByPath($path);
                    return $node ?? [];
                }
                return [];
            })
            ->form(function (array $arguments): array {
                $path = $arguments['nodePath'] ?? null;
                $node = $path ? $this->getNodeByPath($path) : null;
                $type = $node['type'] ?? 'room';

                return match($type) {
                    'room' => $this->getRoomFormSchema(),
                    'room_location' => $this->getLocationFormSchema(),
                    'cabinet_run' => $this->getRunFormSchema(),
                    'cabinet' => $this->getCabinetFormSchema(),
                    default => [TextInput::make('name')->required()],
                };
            })
            ->action(function (array $data, array $arguments): void {
                $path = $arguments['nodePath'] ?? null;
                if ($path) {
                    $this->updateNodeFromAction($data, $path);
                }
            });
    }

    /**
     * Get room form schema for edit action
     */
    protected function getRoomFormSchema(): array
    {
        return [
            Section::make('Room Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Room Name')
                        ->required(),
                    Select::make('room_type')
                        ->label('Room Type')
                        ->options($this->roomTypes)
                        ->required(),
                    TextInput::make('floor_number')
                        ->label('Floor Number')
                        ->numeric(),
                ]),
            Section::make('Pricing')
                ->schema([
                    Select::make('cabinet_level')
                        ->label('Cabinet Level')
                        ->options($this->pricingTiers),
                    Select::make('material_category')
                        ->label('Material Category')
                        ->options($this->materialOptions),
                    Select::make('finish_option')
                        ->label('Finish Option')
                        ->options($this->finishOptions),
                ])
                ->collapsible(),
        ];
    }

    /**
     * Get location form schema for edit action
     */
    protected function getLocationFormSchema(): array
    {
        return [
            Section::make('Location Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Location Name')
                        ->required(),
                    Select::make('location_type')
                        ->label('Location Type')
                        ->options($this->locationTypes)
                        ->required(),
                ]),
            Section::make('Pricing Override')
                ->schema([
                    Select::make('cabinet_level')
                        ->label('Cabinet Level')
                        ->options($this->pricingTiers)
                        ->placeholder('Inherit from room'),
                    Select::make('material_category')
                        ->label('Material Category')
                        ->options($this->materialOptions)
                        ->placeholder('Inherit from room'),
                    Select::make('finish_option')
                        ->label('Finish Option')
                        ->options($this->finishOptions)
                        ->placeholder('Inherit from room'),
                ])
                ->collapsible(),
        ];
    }

    /**
     * Get run form schema for edit action
     */
    protected function getRunFormSchema(): array
    {
        return [
            Section::make('Run Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Run Name')
                        ->required(),
                    Select::make('run_type')
                        ->label('Run Type')
                        ->options($this->runTypes)
                        ->required(),
                ]),
            Section::make('Pricing Override')
                ->schema([
                    Select::make('cabinet_level')
                        ->label('Cabinet Level')
                        ->options($this->pricingTiers)
                        ->placeholder('Inherit from parent'),
                    Select::make('material_category')
                        ->label('Material Category')
                        ->options($this->materialOptions)
                        ->placeholder('Inherit from parent'),
                    Select::make('finish_option')
                        ->label('Finish Option')
                        ->options($this->finishOptions)
                        ->placeholder('Inherit from parent'),
                ])
                ->collapsible(),
        ];
    }

    /**
     * Get cabinet form schema for edit action
     */
    protected function getCabinetFormSchema(): array
    {
        return [
            Section::make('Cabinet Details')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('code')
                                ->label('Cabinet Code')
                                ->placeholder('e.g., B24, W3012, SB36'),
                            Select::make('cabinet_type')
                                ->label('Cabinet Type')
                                ->options($this->cabinetTypes)
                                ->required(),
                        ]),
                    Grid::make(3)
                        ->schema([
                            TextInput::make('length_inches')
                                ->label('Width (in)')
                                ->numeric()
                                ->required()
                                ->minValue(6)
                                ->maxValue(96),
                            TextInput::make('depth_inches')
                                ->label('Depth (in)')
                                ->numeric()
                                ->required()
                                ->minValue(6)
                                ->maxValue(36),
                            TextInput::make('height_inches')
                                ->label('Height (in)')
                                ->numeric()
                                ->required()
                                ->minValue(6)
                                ->maxValue(96),
                        ]),
                    TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(50),
                ]),
        ];
    }

    /**
     * Update node from Filament Action data
     */
    protected function updateNodeFromAction(array $data, string $path): void
    {
        $this->updateNodeByPath($path, $data);
        $this->calculateTotals();
        $this->dispatchSpecDataUpdate();

        $nodeName = $data['name'] ?? 'Item';
        Notification::make()
            ->success()
            ->title('Changes Saved')
            ->body("\"{$nodeName}\" has been updated.")
            ->duration(2000)
            ->send();

        Log::info('CabinetSpecBuilder: Node updated via Filament Action', [
            'path' => $path,
            'data' => $data,
        ]);
    }

    /**
     * Filament Action: Delete a node with confirmation
     */
    public function deleteNodeAction(): Action
    {
        return Action::make('deleteNode')
            ->label('Delete')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalHeading(fn (array $arguments) => 'Delete ' . ucfirst(str_replace('_', ' ', $arguments['nodeType'] ?? 'Item')) . '?')
            ->modalDescription(function (array $arguments) {
                $path = $arguments['nodePath'] ?? null;
                $nodeType = $arguments['nodeType'] ?? 'item';

                if (!$path) {
                    return 'Are you sure you want to delete this item?';
                }

                $node = $this->getNodeByPath($path);
                $childCount = count($node['children'] ?? []);
                $nodeName = $node['name'] ?? 'Untitled';

                if ($childCount > 0) {
                    $childLabel = match($nodeType) {
                        'room' => $childCount === 1 ? '1 location' : "{$childCount} locations",
                        'room_location' => $childCount === 1 ? '1 run' : "{$childCount} runs",
                        'cabinet_run' => $childCount === 1 ? '1 cabinet' : "{$childCount} cabinets",
                        default => $childCount === 1 ? '1 child item' : "{$childCount} child items",
                    };
                    return "This will permanently delete \"{$nodeName}\" and {$childLabel}. This cannot be undone.";
                }

                return "Are you sure you want to delete \"{$nodeName}\"? This cannot be undone.";
            })
            ->modalSubmitActionLabel('Yes, delete it')
            ->successNotificationTitle('Deleted successfully')
            ->action(function (array $arguments): void {
                $path = $arguments['nodePath'] ?? null;
                if ($path) {
                    $node = $this->getNodeByPath($path);
                    $nodeName = $node['name'] ?? 'Item';

                    $this->deleteNodeByPath($path);
                    $this->calculateTotals();
                    $this->dispatchSpecDataUpdate();

                    Notification::make()
                        ->success()
                        ->title('Deleted')
                        ->body("\"{$nodeName}\" has been removed.")
                        ->duration(3000)
                        ->send();

                    Log::info('CabinetSpecBuilder: Node deleted via Filament Action', [
                        'path' => $path,
                        'nodeType' => $arguments['nodeType'] ?? 'unknown',
                    ]);
                }
            });
    }

    /**
     * Filament Action: Duplicate a node (with all children)
     */
    public function duplicateNodeAction(): Action
    {
        return Action::make('duplicateNode')
            ->label('Duplicate')
            ->icon('heroicon-m-document-duplicate')
            ->color('gray')
            ->action(function (array $arguments): void {
                $path = $arguments['nodePath'] ?? null;
                $nodeType = $arguments['nodeType'] ?? 'item';

                if (!$path) {
                    return;
                }

                $node = $this->getNodeByPath($path);
                if (!$node) {
                    return;
                }

                // Deep clone with new IDs
                $clone = $this->deepCloneNode($node);
                $clone['name'] = ($node['name'] ?? 'Item') . ' (Copy)';

                // Insert after the original
                $pathParts = explode('.children.', $path);

                if (count($pathParts) === 1) {
                    // Top-level room
                    $index = (int) $pathParts[0];
                    array_splice($this->specData, $index + 1, 0, [$clone]);
                } else {
                    // Nested node - find parent and insert
                    $parentPath = implode('.children.', array_slice($pathParts, 0, -1));
                    $originalIndex = (int) end($pathParts);

                    $parent = &$this->getNodeRefByPath($parentPath);
                    if ($parent && isset($parent['children'])) {
                        array_splice($parent['children'], $originalIndex + 1, 0, [$clone]);
                    }
                }

                $this->calculateTotals();
                $this->dispatchSpecDataUpdate();

                $originalName = $node['name'] ?? 'Item';
                $childCount = $this->countAllDescendants($clone);
                $childText = $childCount > 0 ? " (including {$childCount} items)" : '';

                Notification::make()
                    ->success()
                    ->title('Duplicated')
                    ->body("\"{$originalName}\" has been copied{$childText}.")
                    ->duration(3000)
                    ->send();

                Log::info('CabinetSpecBuilder: Node duplicated via Filament Action', [
                    'originalPath' => $path,
                    'nodeType' => $nodeType,
                    'childCount' => $childCount,
                ]);
            });
    }

    /**
     * Deep clone a node with new IDs for all children
     */
    protected function deepCloneNode(array $node): array
    {
        $clone = $node;
        $clone['id'] = ($node['type'] ?? 'node') . '_' . Str::random(8);
        $clone['created_at'] = now()->toIso8601String();
        $clone['source'] = 'user';

        if (!empty($clone['children'])) {
            $clone['children'] = array_map(
                fn ($child) => $this->deepCloneNode($child),
                $clone['children']
            );
        }

        return $clone;
    }

    /**
     * Get a reference to a node by path (for modification)
     */
    protected function &getNodeRefByPath(string $path): ?array
    {
        $parts = explode('.', $path);
        $current = &$this->specData;

        foreach ($parts as $part) {
            if ($part === 'children') {
                continue;
            }
            $index = (int) $part;
            if (!isset($current[$index])) {
                $null = null;
                return $null;
            }
            if (isset($current[$index]['children'])) {
                $current = &$current[$index];
            } else {
                $current = &$current[$index];
            }
        }

        return $current;
    }

    /**
     * Count all descendants of a node
     */
    protected function countAllDescendants(array $node): int
    {
        $count = count($node['children'] ?? []);
        foreach ($node['children'] ?? [] as $child) {
            $count += $this->countAllDescendants($child);
        }
        return $count;
    }

    public function render()
    {
        return view('webkul-project::livewire.cabinet-spec-builder');
    }
}
