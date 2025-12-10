<?php

namespace Webkul\Project\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetMaterialsBom;
use Webkul\Project\Models\HardwareRequirement;
use Webkul\Project\Models\TcsMaterialInventoryMapping;
use Webkul\Product\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Material Bill of Materials (BOM) Generation Service
 *
 * Calculates actual inventory material requirements from cabinet specifications
 * based on TCS pricing material categories and their inventory mappings.
 */
class MaterialBomService
{
    /**
     * Generate BOM for a single cabinet specification
     *
     * @param Cabinet $cabinet
     * @return Collection Collection of BOM line items with material requirements
     */
    public function generateBomForCabinet(Cabinet $cabinet): Collection
    {
        if (!$cabinet->material_category) {
            return collect();
        }

        // Get material mappings for this category
        $mappings = $this->getMaterialMappingsForCategory($cabinet->material_category);

        if ($mappings->isEmpty()) {
            return collect();
        }

        $bomItems = collect();

        foreach ($mappings as $mapping) {
            $requirement = $this->calculateMaterialRequirement($cabinet, $mapping);

            if ($requirement['quantity'] > 0) {
                $bomItems->push($requirement);
            }
        }

        return $bomItems;
    }

    /**
     * Generate BOM for multiple cabinet specifications
     *
     * @param Collection|array $cabinets Collection of Cabinet models
     * @return Collection Aggregated BOM with combined material requirements
     */
    public function generateBomForCabinets($cabinets): Collection
    {
        $aggregatedBom = collect();

        foreach ($cabinets as $cabinet) {
            $cabinetBom = $this->generateBomForCabinet($cabinet);

            foreach ($cabinetBom as $item) {
                $this->aggregateBomItem($aggregatedBom, $item);
            }
        }

        return $aggregatedBom->values();
    }

    /**
     * Get material mappings for a specific TCS material category
     *
     * @param string $materialCategory TCS material category slug
     * @return Collection
     */
    protected function getMaterialMappingsForCategory(string $materialCategory): Collection
    {
        return DB::table('tcs_material_inventory_mappings')
            ->where('tcs_material_slug', $materialCategory)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Calculate material requirement for a cabinet + mapping combination
     *
     * @param Cabinet $cabinet
     * @param object $mapping Material inventory mapping record
     * @return array BOM line item with material requirement details
     */
    protected function calculateMaterialRequirement(Cabinet $cabinet, object $mapping): array
    {
        $linearFeet = $cabinet->linear_feet * $cabinet->quantity;
        $quantity = 0;
        $unit = '';

        // Calculate based on material type
        if ($mapping->board_feet_per_lf > 0) {
            // Solid wood - calculate board feet
            $quantity = round($linearFeet * $mapping->board_feet_per_lf, 2);
            $unit = 'board_feet';
        } elseif ($mapping->sheet_sqft_per_lf > 0) {
            // Sheet goods - calculate square footage
            $quantity = round($linearFeet * $mapping->sheet_sqft_per_lf, 2);
            $unit = 'square_feet';
        }

        // Add waste factor (10% standard for woodworking)
        $quantity = round($quantity * 1.10, 2);

        return [
            'mapping_id' => $mapping->id,
            'inventory_product_id' => $mapping->inventory_product_id,
            'wood_species' => $mapping->wood_species,
            'material_category_id' => $mapping->material_category_id,
            'quantity' => $quantity,
            'unit' => $unit,
            'is_box_material' => (bool) $mapping->is_box_material,
            'is_face_frame_material' => (bool) $mapping->is_face_frame_material,
            'is_door_material' => (bool) $mapping->is_door_material,
            'cabinet_id' => $cabinet->id,
            'cabinet_number' => $cabinet->cabinet_number,
        ];
    }

    /**
     * Aggregate a BOM item into the existing collection
     *
     * @param Collection $aggregatedBom
     * @param array $newItem
     * @return void
     */
    protected function aggregateBomItem(Collection $aggregatedBom, array $newItem): void
    {
        $key = $newItem['inventory_product_id'] . '_' . $newItem['wood_species'];

        $existing = $aggregatedBom->get($key);

        if ($existing) {
            // Combine quantities for same material
            $existing['quantity'] += $newItem['quantity'];
            $existing['cabinet_ids'][] = $newItem['cabinet_id'];
            $existing['cabinet_numbers'][] = $newItem['cabinet_number'];

            $aggregatedBom->put($key, $existing);
        } else {
            // Add new material to BOM
            $newItem['cabinet_ids'] = [$newItem['cabinet_id']];
            $newItem['cabinet_numbers'] = [$newItem['cabinet_number']];
            unset($newItem['cabinet_id']);
            unset($newItem['cabinet_number']);

            $aggregatedBom->put($key, $newItem);
        }
    }

    /**
     * Format BOM for display/export
     *
     * @param Collection $bom
     * @param bool $includeProducts Load full product details
     * @return Collection
     */
    public function formatBom(Collection $bom, bool $includeProducts = false): Collection
    {
        return $bom->map(function ($item) use ($includeProducts) {
            $formatted = [
                'wood_species' => $item['wood_species'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'usage' => $this->formatUsageFlags($item),
                'cabinet_count' => count($item['cabinet_ids'] ?? []),
                'cabinet_numbers' => implode(', ', $item['cabinet_numbers'] ?? []),
            ];

            if ($includeProducts && $item['inventory_product_id']) {
                $product = Product::find($item['inventory_product_id']);
                if ($product) {
                    $formatted['product_sku'] = $product->sku;
                    $formatted['product_name'] = $product->name;
                    $formatted['product_id'] = $product->id;
                }
            }

            return $formatted;
        });
    }

    /**
     * Format usage flags into readable string
     *
     * @param array $item
     * @return string
     */
    protected function formatUsageFlags(array $item): string
    {
        $usage = [];

        if ($item['is_box_material']) {
            $usage[] = 'Box';
        }
        if ($item['is_face_frame_material']) {
            $usage[] = 'Face Frame';
        }
        if ($item['is_door_material']) {
            $usage[] = 'Doors';
        }

        return implode(', ', $usage);
    }

    /**
     * Get material cost estimate from BOM
     *
     * @param Collection $bom
     * @return float Total material cost
     */
    public function estimateMaterialCost(Collection $bom): float
    {
        $totalCost = 0;

        foreach ($bom as $item) {
            if ($item['inventory_product_id']) {
                $product = Product::find($item['inventory_product_id']);

                if ($product && $product->cost) {
                    // Cost per unit (board foot or square foot) * quantity
                    $totalCost += $product->cost * $item['quantity'];
                }
            }
        }

        return round($totalCost, 2);
    }

    /**
     * Check material availability in inventory
     *
     * @param Collection $bom
     * @return array Availability status for each material
     */
    public function checkMaterialAvailability(Collection $bom): array
    {
        $availability = [];

        foreach ($bom as $item) {
            if ($item['inventory_product_id']) {
                $product = Product::with('inventoryTransactions')->find($item['inventory_product_id']);

                if ($product) {
                    $availableQty = $product->quantity_on_hand ?? 0;
                    $requiredQty = $item['quantity'];

                    $availability[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'wood_species' => $item['wood_species'],
                        'required' => $requiredQty,
                        'available' => $availableQty,
                        'sufficient' => $availableQty >= $requiredQty,
                        'shortage' => max(0, $requiredQty - $availableQty),
                        'unit' => $item['unit'],
                    ];
                }
            }
        }

        return $availability;
    }

    /**
     * Generate sales order line items from BOM
     *
     * @param Collection $bom
     * @param int $orderId
     * @return Collection Created order line items
     */
    public function createOrderLinesFromBom(Collection $bom, int $orderId): Collection
    {
        $orderLines = collect();

        foreach ($bom as $item) {
            if ($item['inventory_product_id']) {
                $product = Product::find($item['inventory_product_id']);

                if ($product) {
                    $lineData = [
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->cost ?? 0,
                        'subtotal' => ($product->cost ?? 0) * $item['quantity'],
                        'description' => "{$item['wood_species']} - {$this->formatUsageFlags($item)}",
                        'notes' => "Required for cabinets: " . implode(', ', $item['cabinet_numbers'] ?? []),
                    ];

                    // Note: Actual OrderLine creation would go here
                    // For now, just collect the data
                    $orderLines->push($lineData);
                }
            }
        }

        return $orderLines;
    }

    /**
     * Get material recommendations based on cabinet specifications
     *
     * Suggests which materials to use based on usage type and priority
     *
     * @param Cabinet $cabinet
     * @param string $usageType box|face_frame|door
     * @return Collection
     */
    public function getMaterialRecommendations(Cabinet $cabinet, string $usageType): Collection
    {
        if (!$cabinet->material_category) {
            return collect();
        }

        $usageColumn = match ($usageType) {
            'box' => 'is_box_material',
            'face_frame' => 'is_face_frame_material',
            'door' => 'is_door_material',
            default => 'is_box_material',
        };

        return DB::table('tcs_material_inventory_mappings')
            ->where('tcs_material_slug', $cabinet->material_category)
            ->where($usageColumn, true)
            ->where('is_active', true)
            ->orderBy('priority')
            ->limit(3)
            ->get()
            ->map(function ($mapping) {
                return [
                    'wood_species' => $mapping->wood_species,
                    'board_feet_per_lf' => $mapping->board_feet_per_lf,
                    'sheet_sqft_per_lf' => $mapping->sheet_sqft_per_lf,
                    'priority' => $mapping->priority,
                    'notes' => $mapping->notes,
                ];
            });
    }

    // =========================================================================
    // Hardware BOM Generation Methods
    // =========================================================================

    /**
     * Generate BOM items from hardware requirements for a cabinet run
     *
     * @param CabinetRun $cabinetRun
     * @return Collection Created CabinetMaterialsBom records
     */
    public function generateBomFromHardwareForRun(CabinetRun $cabinetRun): Collection
    {
        $createdBomItems = collect();

        // Get all hardware requirements for this run
        $hardwareRequirements = HardwareRequirement::where('cabinet_run_id', $cabinetRun->id)
            ->whereNotNull('product_id')
            ->with('product')
            ->get();

        foreach ($hardwareRequirements as $hardware) {
            $bomItem = $this->createBomFromHardware($hardware, $cabinetRun);
            if ($bomItem) {
                $createdBomItems->push($bomItem);
            }
        }

        return $createdBomItems;
    }

    /**
     * Generate BOM items from hardware requirements for a cabinet
     *
     * @param Cabinet $cabinet
     * @return Collection Created CabinetMaterialsBom records
     */
    public function generateBomFromHardwareForCabinet(Cabinet $cabinet): Collection
    {
        $createdBomItems = collect();

        // Get all hardware requirements for this cabinet
        $hardwareRequirements = HardwareRequirement::where('cabinet_id', $cabinet->id)
            ->whereNotNull('product_id')
            ->with('product')
            ->get();

        foreach ($hardwareRequirements as $hardware) {
            $bomItem = $this->createBomFromHardware($hardware, $cabinet->cabinetRun, $cabinet);
            if ($bomItem) {
                $createdBomItems->push($bomItem);
            }
        }

        return $createdBomItems;
    }

    /**
     * Generate BOM items from all hardware requirements for a project
     *
     * @param int $projectId
     * @return Collection Created CabinetMaterialsBom records
     */
    public function generateBomFromHardwareForProject(int $projectId): Collection
    {
        $createdBomItems = collect();

        // Get all cabinet runs for the project through the hierarchy
        $cabinetRuns = CabinetRun::whereHas('roomLocation.room', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })->with(['cabinets', 'hardwareRequirements.product'])->get();

        foreach ($cabinetRuns as $run) {
            // Run-level hardware
            foreach ($run->hardwareRequirements as $hardware) {
                if ($hardware->product_id) {
                    $bomItem = $this->createBomFromHardware($hardware, $run);
                    if ($bomItem) {
                        $createdBomItems->push($bomItem);
                    }
                }
            }

            // Cabinet-level hardware
            foreach ($run->cabinets as $cabinet) {
                $cabinetHardware = HardwareRequirement::where('cabinet_id', $cabinet->id)
                    ->whereNotNull('product_id')
                    ->with('product')
                    ->get();

                foreach ($cabinetHardware as $hardware) {
                    $bomItem = $this->createBomFromHardware($hardware, $run, $cabinet);
                    if ($bomItem) {
                        $createdBomItems->push($bomItem);
                    }
                }
            }
        }

        return $createdBomItems;
    }

    /**
     * Create a single BOM record from a hardware requirement
     *
     * @param HardwareRequirement $hardware
     * @param CabinetRun|null $cabinetRun
     * @param Cabinet|null $cabinet
     * @return CabinetMaterialsBom|null
     */
    protected function createBomFromHardware(
        HardwareRequirement $hardware,
        ?CabinetRun $cabinetRun = null,
        ?Cabinet $cabinet = null
    ): ?CabinetMaterialsBom {
        if (!$hardware->product_id) {
            return null;
        }

        $product = $hardware->product ?? Product::find($hardware->product_id);
        if (!$product) {
            return null;
        }

        // Check if BOM item already exists for this hardware
        $existingBom = CabinetMaterialsBom::where('product_id', $hardware->product_id)
            ->where(function ($query) use ($cabinet, $cabinetRun) {
                if ($cabinet) {
                    $query->where('cabinet_id', $cabinet->id);
                } elseif ($cabinetRun) {
                    $query->where('cabinet_run_id', $cabinetRun->id)
                        ->whereNull('cabinet_id');
                }
            })
            ->first();

        if ($existingBom) {
            // Update existing BOM quantity instead of creating duplicate
            $existingBom->update([
                'quantity_required' => $existingBom->quantity_required + $hardware->quantity_required,
            ]);
            return $existingBom;
        }

        // Create new BOM item
        $componentName = $this->formatHardwareComponentName($hardware);

        return CabinetMaterialsBom::create([
            'cabinet_id' => $cabinet?->id,
            'cabinet_run_id' => $cabinetRun?->id,
            'product_id' => $hardware->product_id,
            'component_name' => $componentName,
            'quantity_required' => $hardware->quantity_required,
            'unit_of_measure' => $hardware->unit_of_measure ?? 'EA',
            'waste_factor_percentage' => 0, // No waste for hardware
            'quantity_with_waste' => $hardware->quantity_required,
            'unit_cost' => $hardware->unit_cost ?? $product->cost ?? 0,
            'total_material_cost' => $hardware->total_hardware_cost ?? 0,
            'cnc_notes' => null,
            'machining_operations' => null,
            'material_allocated' => $hardware->hardware_allocated,
            'material_allocated_at' => $hardware->hardware_allocated_at,
            'material_issued' => $hardware->hardware_issued,
            'material_issued_at' => $hardware->hardware_issued_at,
        ]);
    }

    /**
     * Format hardware component name for BOM
     *
     * @param HardwareRequirement $hardware
     * @return string
     */
    protected function formatHardwareComponentName(HardwareRequirement $hardware): string
    {
        $parts = [];

        // Hardware type
        $type = ucfirst(str_replace('_', ' ', $hardware->hardware_type ?? 'Hardware'));
        $parts[] = $type;

        // Model number if available
        if ($hardware->model_number) {
            $parts[] = $hardware->model_number;
        }

        // Application details
        if ($hardware->applied_to) {
            $parts[] = "({$hardware->applied_to})";
        }

        return implode(' - ', $parts);
    }

    /**
     * Sync BOM with hardware requirements (updates quantities, adds new, removes orphaned)
     *
     * @param CabinetRun $cabinetRun
     * @return array Summary of changes: ['added' => int, 'updated' => int, 'removed' => int]
     */
    public function syncBomWithHardwareForRun(CabinetRun $cabinetRun): array
    {
        $summary = ['added' => 0, 'updated' => 0, 'removed' => 0];

        // Get current hardware requirements
        $hardwareRequirements = HardwareRequirement::where('cabinet_run_id', $cabinetRun->id)
            ->orWhereHas('cabinet', function ($q) use ($cabinetRun) {
                $q->where('cabinet_run_id', $cabinetRun->id);
            })
            ->whereNotNull('product_id')
            ->get();

        $hardwareProductIds = $hardwareRequirements->pluck('product_id')->unique()->toArray();

        // Get existing BOM items for hardware (exclude cabinet materials by checking component_name or product type)
        $existingHardwareBom = CabinetMaterialsBom::where('cabinet_run_id', $cabinetRun->id)
            ->whereIn('product_id', $hardwareProductIds)
            ->get()
            ->keyBy('product_id');

        // Process each hardware requirement
        foreach ($hardwareRequirements as $hardware) {
            if (!$hardware->product_id) {
                continue;
            }

            $existingBom = $existingHardwareBom->get($hardware->product_id);

            if ($existingBom) {
                // Update existing
                if ($existingBom->quantity_required != $hardware->quantity_required) {
                    $existingBom->update(['quantity_required' => $hardware->quantity_required]);
                    $summary['updated']++;
                }
            } else {
                // Create new
                $this->createBomFromHardware($hardware, $cabinetRun, $hardware->cabinet);
                $summary['added']++;
            }
        }

        // Remove BOM items for hardware that no longer exists
        $orphanedBomItems = CabinetMaterialsBom::where('cabinet_run_id', $cabinetRun->id)
            ->whereIn('product_id', $hardwareProductIds)
            ->whereNotIn('product_id', $hardwareRequirements->pluck('product_id')->toArray())
            ->get();

        foreach ($orphanedBomItems as $orphan) {
            $orphan->delete();
            $summary['removed']++;
        }

        return $summary;
    }

    /**
     * Get hardware BOM summary for display
     *
     * @param CabinetRun $cabinetRun
     * @return array Aggregated hardware by type with totals
     */
    public function getHardwareBomSummary(CabinetRun $cabinetRun): array
    {
        $hardware = HardwareRequirement::where('cabinet_run_id', $cabinetRun->id)
            ->orWhereHas('cabinet', function ($q) use ($cabinetRun) {
                $q->where('cabinet_run_id', $cabinetRun->id);
            })
            ->with('product')
            ->get();

        $summary = [
            'hinges' => ['count' => 0, 'items' => [], 'cost' => 0],
            'slides' => ['count' => 0, 'items' => [], 'cost' => 0],
            'shelf_pins' => ['count' => 0, 'items' => [], 'cost' => 0],
            'pullouts' => ['count' => 0, 'items' => [], 'cost' => 0],
            'other' => ['count' => 0, 'items' => [], 'cost' => 0],
        ];

        foreach ($hardware as $item) {
            $type = $item->hardware_type;
            $category = match ($type) {
                'hinge' => 'hinges',
                'slide' => 'slides',
                'shelf_pin' => 'shelf_pins',
                'pullout' => 'pullouts',
                default => 'other',
            };

            $summary[$category]['count'] += $item->quantity_required;
            $summary[$category]['cost'] += $item->total_hardware_cost ?? 0;
            $summary[$category]['items'][] = [
                'id' => $item->id,
                'model_number' => $item->model_number,
                'quantity' => $item->quantity_required,
                'unit_cost' => $item->unit_cost,
                'total_cost' => $item->total_hardware_cost,
                'allocated' => $item->hardware_allocated,
                'product_name' => $item->product?->name,
            ];
        }

        $summary['total_items'] = array_sum(array_column($summary, 'count'));
        $summary['total_cost'] = array_sum(array_column($summary, 'cost'));

        return $summary;
    }
}
