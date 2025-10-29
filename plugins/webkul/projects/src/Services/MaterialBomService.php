<?php

namespace Webkul\Project\Services;

use Webkul\Project\Models\CabinetSpecification;
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
     * @param CabinetSpecification $cabinet
     * @return Collection Collection of BOM line items with material requirements
     */
    public function generateBomForCabinet(CabinetSpecification $cabinet): Collection
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
     * @param Collection|array $cabinets Collection of CabinetSpecification models
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
     * @param CabinetSpecification $cabinet
     * @param object $mapping Material inventory mapping record
     * @return array BOM line item with material requirement details
     */
    protected function calculateMaterialRequirement(CabinetSpecification $cabinet, object $mapping): array
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
     * @param CabinetSpecification $cabinet
     * @param string $usageType box|face_frame|door
     * @return Collection
     */
    public function getMaterialRecommendations(CabinetSpecification $cabinet, string $usageType): Collection
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
}
