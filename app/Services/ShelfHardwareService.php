<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

/**
 * Auto-assigns shelf hardware (pins, edge banding) based on shelf dimensions.
 * Uses the EAV product attribute system for hardware specifications.
 * 
 * Shop Practice:
 * - Shelf pins: 5mm diameter, generic/interchangeable
 * - Edge banding: Front edge only, matches cabinet interior
 * - Edge banding sold in 500' rolls
 */
class ShelfHardwareService
{
    /**
     * Cached shelf pin products (populated from database on first use)
     */
    protected ?array $shelfPinProducts = null;

    /**
     * Default shelf pin product ID (fallback)
     */
    protected ?int $defaultShelfPinProductId = null;

    // ========================================
    // SHELF PIN SELECTION
    // ========================================

    /**
     * Get shelf pin product from inventory.
     * 
     * Queries products with "Shelf Pin" or "5mm" in name/attributes.
     * Falls back to generic if not found.
     * 
     * @return array Shelf pin product info
     */
    public function getShelfPinProduct(): array
    {
        $this->ensureShelfPinProductsLoaded();

        if (!empty($this->shelfPinProducts)) {
            return $this->shelfPinProducts[0];  // Return first match
        }

        // Fallback to generic
        return $this->getGenericShelfPin();
    }

    /**
     * Get shelf pin product model.
     * 
     * @return Product|null
     */
    public function getShelfPinProductModel(): ?Product
    {
        $pinInfo = $this->getShelfPinProduct();
        
        if ($pinInfo['product_id']) {
            return Product::find($pinInfo['product_id']);
        }
        
        return null;
    }

    /**
     * Load shelf pin products from database.
     */
    protected function ensureShelfPinProductsLoaded(): void
    {
        if ($this->shelfPinProducts !== null) {
            return;
        }

        $this->shelfPinProducts = [];

        // Search for products with "shelf pin" in name
        $pins = DB::table('products_products')
            ->where('name', 'LIKE', '%shelf%pin%')
            ->orWhere('name', 'LIKE', '%5mm%pin%')
            ->orWhere('name', 'LIKE', '%5 mm%pin%')
            ->select('id', 'name', 'price')
            ->get();

        foreach ($pins as $pin) {
            $this->shelfPinProducts[] = [
                'product_id' => $pin->id,
                'name' => $pin->name,
                'price' => $pin->price ?? 0,
                'diameter_mm' => 5.0,
                'type' => 'spoon',  // Most common
            ];
        }
    }

    /**
     * Generic shelf pin fallback when no product found.
     */
    protected function getGenericShelfPin(): array
    {
        return [
            'product_id' => null,
            'name' => '5mm Shelf Pin (Generic)',
            'price' => 0.10,  // Typical price per pin
            'diameter_mm' => 5.0,
            'type' => 'spoon',
            'notes' => 'Generic - no specific product in inventory',
        ];
    }

    // ========================================
    // EDGE BANDING
    // ========================================

    /**
     * Calculate edge banding requirements for a shelf.
     * 
     * Shop practice: Front edge ONLY is edge banded.
     * Back and sides are NOT edge banded.
     * 
     * @param float $shelfWidth Shelf width in inches
     * @param bool $includeFront Edge band front (default true)
     * @param bool $includeBack Edge band back (default false)
     * @param bool $includeSides Edge band sides (default false)
     * @return array Edge banding requirements
     */
    public function getEdgeBandingRequirement(
        float $shelfWidth,
        float $shelfDepth = 0,
        bool $includeFront = true,
        bool $includeBack = false,
        bool $includeSides = false
    ): array {
        $totalLength = 0;
        $edges = [];

        if ($includeFront) {
            $totalLength += $shelfWidth;
            $edges[] = [
                'edge' => 'front',
                'length' => $shelfWidth,
            ];
        }

        if ($includeBack && $shelfWidth > 0) {
            $totalLength += $shelfWidth;
            $edges[] = [
                'edge' => 'back',
                'length' => $shelfWidth,
            ];
        }

        if ($includeSides && $shelfDepth > 0) {
            $totalLength += $shelfDepth * 2;  // Both sides
            $edges[] = [
                'edge' => 'left',
                'length' => $shelfDepth,
            ];
            $edges[] = [
                'edge' => 'right',
                'length' => $shelfDepth,
            ];
        }

        // Convert to feet for roll calculation
        $totalFeet = $totalLength / 12;

        return [
            'edges' => $edges,
            'total_length_inches' => round($totalLength, 4),
            'total_length_feet' => round($totalFeet, 4),
            'material' => 'Match cabinet interior (pre-finished maple typical)',
            'roll_size_feet' => 500,  // Standard roll size
            'notes' => 'Front edge only (shop standard)',
        ];
    }

    /**
     * Get edge banding product from inventory.
     * 
     * @param string $material Material type to match
     * @return array|null Edge banding product info
     */
    public function getEdgeBandingProduct(?string $material = null): ?array
    {
        $query = DB::table('products_products')
            ->where('name', 'LIKE', '%edge%band%');
        
        if ($material) {
            $query->where('name', 'LIKE', "%{$material}%");
        }

        $product = $query->first();

        if ($product) {
            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price ?? 0,
            ];
        }

        return null;
    }

    // ========================================
    // COMPLETE HARDWARE LIST
    // ========================================

    /**
     * Get all hardware requirements for a shelf.
     * 
     * @param float $shelfWidth Shelf width in inches
     * @param float $shelfDepth Shelf depth in inches
     * @param bool $hasCenterSupport Has center support column
     * @return array Complete hardware list with costs
     */
    public function getHardwareForShelf(
        float $shelfWidth,
        float $shelfDepth,
        bool $hasCenterSupport = false
    ): array {
        // Get shelf pin requirements
        $pinProduct = $this->getShelfPinProduct();
        $pinQuantity = $hasCenterSupport ? 6 : 4;  // 4 corners or 6 with center
        $pinCost = $pinProduct['price'] * $pinQuantity;

        // Get edge banding requirements (front edge only)
        $edgeBanding = $this->getEdgeBandingRequirement($shelfWidth, $shelfDepth);

        return [
            'shelf_pins' => [
                'product_id' => $pinProduct['product_id'],
                'name' => $pinProduct['name'],
                'diameter_mm' => $pinProduct['diameter_mm'],
                'quantity' => $pinQuantity,
                'unit_price' => $pinProduct['price'],
                'total_cost' => round($pinCost, 2),
            ],
            'edge_banding' => $edgeBanding,
            'summary' => [
                'shelf_width' => $shelfWidth,
                'shelf_depth' => $shelfDepth,
                'has_center_support' => $hasCenterSupport,
                'total_pins' => $pinQuantity,
                'edge_band_length_inches' => $edgeBanding['total_length_inches'],
                'estimated_hardware_cost' => round($pinCost, 2),
            ],
        ];
    }

    /**
     * Get hardware for multiple shelves in a cabinet.
     * 
     * @param array $shelves Array of shelf info [['width' => X, 'depth' => Y, 'center_support' => bool], ...]
     * @return array Combined hardware list
     */
    public function getHardwareForCabinet(array $shelves): array
    {
        $totalPins = 0;
        $totalEdgeBandLength = 0;
        $pinProduct = $this->getShelfPinProduct();

        foreach ($shelves as $shelf) {
            $width = $shelf['width'] ?? $shelf['cut_width'] ?? 0;
            $depth = $shelf['depth'] ?? $shelf['cut_depth'] ?? 0;
            $hasCenterSupport = $shelf['center_support'] ?? $shelf['has_center_support'] ?? false;

            $totalPins += $hasCenterSupport ? 6 : 4;
            $totalEdgeBandLength += $width;  // Front edge only
        }

        $totalPinCost = $pinProduct['price'] * $totalPins;

        return [
            'shelf_pins' => [
                'product_id' => $pinProduct['product_id'],
                'name' => $pinProduct['name'],
                'quantity' => $totalPins,
                'unit_price' => $pinProduct['price'],
                'total_cost' => round($totalPinCost, 2),
            ],
            'edge_banding' => [
                'total_length_inches' => round($totalEdgeBandLength, 4),
                'total_length_feet' => round($totalEdgeBandLength / 12, 4),
                'edges' => 'Front only (all shelves)',
            ],
            'summary' => [
                'shelf_count' => count($shelves),
                'total_pins' => $totalPins,
                'total_edge_band_inches' => round($totalEdgeBandLength, 4),
                'estimated_hardware_cost' => round($totalPinCost, 2),
            ],
        ];
    }

    // ========================================
    // HARDWARE REQUIREMENTS RECORDS
    // ========================================

    /**
     * Create hardware requirement records for a cabinet's shelves.
     * 
     * @param int $cabinetId The cabinet ID
     * @param int $roomId The room ID
     * @param array $shelves Array of shelf info
     * @return array Created hardware requirement IDs
     */
    public function createHardwareRequirements(int $cabinetId, int $roomId, array $shelves): array
    {
        $hardware = $this->getHardwareForCabinet($shelves);
        $createdIds = [];

        // Create shelf pin requirements
        if ($hardware['shelf_pins']['product_id']) {
            $id = DB::table('hardware_requirements')->insertGetId([
                'room_id' => $roomId,
                'cabinet_id' => $cabinetId,
                'product_id' => $hardware['shelf_pins']['product_id'],
                'hardware_type' => 'shelf_pin',
                'quantity_required' => $hardware['shelf_pins']['quantity'],
                'unit_of_measure' => 'EA',
                'applied_to' => 'shelves',
                'unit_cost' => $hardware['shelf_pins']['unit_price'],
                'total_hardware_cost' => $hardware['shelf_pins']['total_cost'],
                'installation_notes' => 'Auto-assigned based on shelf count',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $createdIds['shelf_pins'] = $id;
        }

        $createdIds['summary'] = $hardware['summary'];

        return $createdIds;
    }

    /**
     * Force refresh from database.
     */
    public function refreshFromDatabase(): void
    {
        $this->shelfPinProducts = null;
        $this->ensureShelfPinProductsLoaded();
    }
}
