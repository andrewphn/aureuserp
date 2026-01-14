<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

/**
 * Auto-assigns drawer hardware (slides, locking devices) based on drawer dimensions.
 * Uses the EAV product attribute system for slide specifications.
 */
class DrawerHardwareService
{
    /**
     * Cached slide map (populated from database on first use)
     */
    protected ?array $slideMap = null;

    /**
     * Locking device product ID
     */
    protected int $lockingDeviceProductId = 23517;

    /**
     * Get appropriate slide based on drawer depth using attribute system.
     * 
     * Slide selection rules:
     * - Slide length should be <= drawer depth (must fit)
     * - Use the longest slide that fits for maximum extension
     * - Standard sizes: 15", 18", 21"
     * 
     * @param float $drawerDepthInches The interior depth of the drawer box
     * @return array Slide product info with recommended length
     */
    public function getSlideForDepth(float $drawerDepthInches): array
    {
        // Ensure slide map is populated
        $this->ensureSlideMapLoaded();

        // Find best matching slide (longest that fits within drawer depth)
        $selectedSlide = null;
        $selectedLength = 0;

        $availableLengths = array_keys($this->slideMap);
        sort($availableLengths);

        foreach ($availableLengths as $length) {
            // Slide must fit within drawer depth
            if ($length <= $drawerDepthInches && $length > $selectedLength) {
                $selectedSlide = $this->slideMap[$length];
                $selectedLength = $length;
            }
        }

        // If drawer is too shallow for any slide, use smallest available
        if (!$selectedSlide) {
            $selectedLength = min($availableLengths);
            $selectedSlide = $this->slideMap[$selectedLength];
        }

        return array_merge($selectedSlide, [
            'slide_length_inches' => $selectedLength,
            'drawer_depth_inches' => $drawerDepthInches,
            'quantity_per_drawer' => 2, // Always 2 slides per drawer (left + right)
        ]);
    }

    /**
     * Get slide product by drawer depth, returning the Product model.
     * 
     * @param float $drawerDepthInches The interior depth of the drawer box
     * @return Product|null The slide product model
     */
    public function getSlideProductForDepth(float $drawerDepthInches): ?Product
    {
        $slideInfo = $this->getSlideForDepth($drawerDepthInches);
        
        return Product::find($slideInfo['product_id']);
    }

    /**
     * Get all specifications for a slide product from its attributes.
     * 
     * @param Product $slide The slide product
     * @return array Associative array of spec name => value
     */
    public function getSlideSpecs(Product $slide): array
    {
        return [
            'length' => $slide->getSpecValue('Slide Length'),
            'min_cabinet_depth' => $slide->getSpecValue('Min Cabinet Depth'),
            'weight_capacity' => $slide->getSpecValue('Weight Capacity'),
            'side_clearance' => $slide->getSpecValue('Slide Side Clearance'),
            'top_clearance' => $slide->getSpecValue('Slide Top Clearance'),
            'bottom_clearance' => $slide->getSpecValue('Slide Bottom Clearance'),
            'rear_clearance' => $slide->getSpecValue('Slide Rear Clearance'),
        ];
    }

    /**
     * Ensure slide map is loaded from database attributes.
     */
    protected function ensureSlideMapLoaded(): void
    {
        if ($this->slideMap !== null) {
            return;
        }

        $this->slideMap = [];

        // Get attribute ID for "Slide Length"
        $slideLengthAttrId = DB::table('products_attributes')
            ->where('name', 'Slide Length')
            ->value('id');

        if (!$slideLengthAttrId) {
            // Fall back to hardcoded if attributes not set up
            $this->slideMap = $this->getHardcodedSlideMap();
            return;
        }

        // Query products with Slide Length attribute values
        $slides = DB::table('products_products as p')
            ->join('products_product_attribute_values as pav', 'p.id', '=', 'pav.product_id')
            ->where('pav.attribute_id', $slideLengthAttrId)
            ->whereNotNull('pav.numeric_value')
            ->select('p.id', 'p.name', 'p.price', 'pav.numeric_value as slide_length')
            ->get();

        foreach ($slides as $slide) {
            $length = (int) $slide->slide_length;
            $this->slideMap[$length] = [
                'product_id' => $slide->id,
                'model' => $this->extractModelNumber($slide->name),
                'name' => $slide->name,
                'price' => $slide->price ?? 0,
            ];
        }

        // If no slides found from attributes, use hardcoded fallback
        if (empty($this->slideMap)) {
            $this->slideMap = $this->getHardcodedSlideMap();
        }
    }

    /**
     * Hardcoded fallback slide map for backwards compatibility.
     */
    protected function getHardcodedSlideMap(): array
    {
        return [
            15 => [
                'product_id' => 23530,
                'model' => '563H3810B',
                'name' => 'BLUM slide runner 15"',
                'price' => 17.50,
            ],
            18 => [
                'product_id' => 23528,
                'model' => '563H4570B',
                'name' => 'Blum slide runner 18"',
                'price' => 14.23,
            ],
            21 => [
                'product_id' => 23529,
                'model' => '563H5330B',
                'name' => 'Blum slide runner 21"',
                'price' => 32.61,
            ],
        ];
    }

    /**
     * Get locking device info from database or fallback.
     */
    protected function getLockingDevice(): array
    {
        $product = DB::table('products_products')
            ->where('id', $this->lockingDeviceProductId)
            ->first();

        if ($product) {
            return [
                'product_id' => $product->id,
                'model' => $this->extractModelNumber($product->name),
                'name' => $product->name,
                'price' => $product->price ?? 0.73,
                'quantity_per_drawer' => 1,
            ];
        }

        // Fallback
        return [
            'product_id' => 23517,
            'model' => 'T51.1901',
            'name' => 'Blum Tandem 563/569 Locking Device',
            'price' => 0.73,
            'quantity_per_drawer' => 1,
        ];
    }

    /**
     * Get all hardware requirements for a drawer
     * 
     * @param float $drawerDepthInches Drawer box depth
     * @param int $drawerCount Number of drawers
     * @param bool $includeLockingDevice Include locking devices
     * @return array Complete hardware list with costs
     */
    public function getHardwareForDrawers(
        float $drawerDepthInches,
        int $drawerCount = 1,
        bool $includeLockingDevice = true
    ): array {
        $slide = $this->getSlideForDepth($drawerDepthInches);
        $lockingDevice = $this->getLockingDevice();
        
        $hardware = [
            'slides' => [
                'product_id' => $slide['product_id'],
                'model' => $slide['model'],
                'name' => $slide['name'],
                'slide_length_inches' => $slide['slide_length_inches'],
                'quantity' => $drawerCount * 2, // 2 per drawer
                'unit_price' => $slide['price'],
                'total_cost' => $slide['price'] * $drawerCount * 2,
            ],
        ];

        if ($includeLockingDevice) {
            $hardware['locking_devices'] = [
                'product_id' => $lockingDevice['product_id'],
                'model' => $lockingDevice['model'],
                'name' => $lockingDevice['name'],
                'quantity' => $drawerCount * $lockingDevice['quantity_per_drawer'],
                'unit_price' => $lockingDevice['price'],
                'total_cost' => $lockingDevice['price'] * $drawerCount,
            ];
        }

        // Calculate totals
        $hardware['summary'] = [
            'drawer_count' => $drawerCount,
            'drawer_depth_inches' => $drawerDepthInches,
            'recommended_slide_length' => $slide['slide_length_inches'],
            'total_hardware_cost' => array_sum(array_column($hardware, 'total_cost')),
        ];

        return $hardware;
    }

    /**
     * Get hardware for a cabinet with multiple drawers of varying depths
     * 
     * @param array $drawers Array of drawer info: [['depth' => 18, 'count' => 1], ...]
     * @return array Complete hardware list
     */
    public function getHardwareForCabinet(array $drawers): array
    {
        $totalCost = 0;
        $totalDrawers = 0;
        $slidesByLength = [];
        $lockingDevice = $this->getLockingDevice();

        foreach ($drawers as $drawer) {
            $depth = $drawer['depth'] ?? $drawer['depth_inches'] ?? 18;
            $count = $drawer['count'] ?? 1;
            $totalDrawers += $count;

            $slide = $this->getSlideForDepth($depth);
            $length = $slide['slide_length_inches'];

            // Aggregate slides by length
            if (!isset($slidesByLength[$length])) {
                $slidesByLength[$length] = [
                    'product_id' => $slide['product_id'],
                    'model' => $slide['model'],
                    'name' => $slide['name'],
                    'slide_length_inches' => $length,
                    'unit_price' => $slide['price'],
                    'quantity' => 0,
                    'total_cost' => 0,
                ];
            }
            $slidesByLength[$length]['quantity'] += $count * 2;
            $slidesByLength[$length]['total_cost'] = 
                $slidesByLength[$length]['quantity'] * $slidesByLength[$length]['unit_price'];
        }

        // Add locking devices
        $lockingDevices = [
            'product_id' => $lockingDevice['product_id'],
            'model' => $lockingDevice['model'],
            'name' => $lockingDevice['name'],
            'quantity' => $totalDrawers,
            'unit_price' => $lockingDevice['price'],
            'total_cost' => $lockingDevice['price'] * $totalDrawers,
        ];

        // Calculate total cost
        foreach ($slidesByLength as $slide) {
            $totalCost += $slide['total_cost'];
        }
        $totalCost += $lockingDevices['total_cost'];

        return [
            'slides' => array_values($slidesByLength),
            'locking_devices' => $lockingDevices,
            'summary' => [
                'total_drawers' => $totalDrawers,
                'total_slides' => $totalDrawers * 2,
                'total_locking_devices' => $totalDrawers,
                'total_hardware_cost' => round($totalCost, 2),
            ],
        ];
    }

    /**
     * Auto-assign hardware from DWG-extracted drawer data
     * 
     * @param array $dwgData Parsed DWG data with drawer dimensions
     * @return array Hardware assignments for each drawer
     */
    public function assignFromDwgData(array $dwgData): array
    {
        $assignments = [];

        // Look for drawer-related dimensions in DWG text
        $drawerDepths = $this->extractDrawerDepths($dwgData);

        foreach ($drawerDepths as $index => $depth) {
            $hardware = $this->getHardwareForDrawers($depth, 1);
            $assignments[] = [
                'drawer_index' => $index + 1,
                'detected_depth' => $depth,
                'hardware' => $hardware,
            ];
        }

        return $assignments;
    }

    /**
     * Extract drawer depths from DWG text/dimensions
     */
    protected function extractDrawerDepths(array $dwgData): array
    {
        $depths = [];
        
        // Default depths if not explicitly found
        // Common drawer depths: 18", 21", 15"
        $defaultDepth = 18;

        // Look for dimension text near "drawer" labels
        $texts = $dwgData['texts'] ?? [];
        $dimensions = $dwgData['dimensions'] ?? [];

        // If we found explicit depth dimensions, use them
        foreach ($dimensions as $dim) {
            $value = $dim['measurement'] ?? $dim['value'] ?? 0;
            // Typical drawer depths are 12-24 inches
            if ($value >= 12 && $value <= 24) {
                $depths[] = $value;
            }
        }

        // If no depths found, use default
        if (empty($depths)) {
            $depths[] = $defaultDepth;
        }

        return $depths;
    }

    /**
     * Get available slide lengths
     */
    public function getAvailableSlideLengths(): array
    {
        $this->ensureSlideMapLoaded();
        return array_keys($this->slideMap);
    }

    /**
     * Force refresh slide map from database
     */
    public function refreshFromDatabase(): void
    {
        $this->slideMap = null;
        $this->ensureSlideMapLoaded();
    }

    /**
     * Extract model number from product name
     */
    protected function extractModelNumber(string $name): string
    {
        if (preg_match('/(\d{3}[A-Z]\d+[A-Z]?)/i', $name, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Create hardware requirement records for a cabinet's drawers
     * 
     * @param int $cabinetId The cabinet ID
     * @param int $roomId The room ID  
     * @param array $drawers Array of drawer info [['depth' => 18, 'count' => 1], ...]
     * @return array Created hardware requirement IDs
     */
    public function createHardwareRequirements(int $cabinetId, int $roomId, array $drawers): array
    {
        $hardware = $this->getHardwareForCabinet($drawers);
        $createdIds = [];

        // Create slide requirements
        foreach ($hardware['slides'] as $slide) {
            $id = DB::table('hardware_requirements')->insertGetId([
                'room_id' => $roomId,
                'cabinet_id' => $cabinetId,
                'product_id' => $slide['product_id'],
                'hardware_type' => 'slide',
                'manufacturer' => 'Blum',
                'model_number' => $slide['model'],
                'quantity_required' => $slide['quantity'],
                'unit_of_measure' => 'EA',
                'applied_to' => 'drawers',
                'slide_type' => 'Tandem',
                'slide_length_inches' => $slide['slide_length_inches'],
                'unit_cost' => $slide['unit_price'],
                'total_hardware_cost' => $slide['total_cost'],
                'installation_notes' => 'Auto-assigned based on drawer depth',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $createdIds['slides'][] = $id;
        }

        // Create locking device requirements
        $lockDev = $hardware['locking_devices'];
        $id = DB::table('hardware_requirements')->insertGetId([
            'room_id' => $roomId,
            'cabinet_id' => $cabinetId,
            'product_id' => $lockDev['product_id'],
            'hardware_type' => 'accessory',
            'manufacturer' => 'Blum',
            'model_number' => $lockDev['model'],
            'quantity_required' => $lockDev['quantity'],
            'unit_of_measure' => 'EA',
            'applied_to' => 'drawers',
            'unit_cost' => $lockDev['unit_price'],
            'total_hardware_cost' => $lockDev['total_cost'],
            'installation_notes' => 'Auto-assigned (required for Tandem slides)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $createdIds['locking_devices'] = $id;

        $createdIds['summary'] = $hardware['summary'];

        return $createdIds;
    }

    /**
     * Auto-assign hardware for all drawers in a project
     * 
     * @param int $projectId Project ID
     * @return array Summary of assignments
     */
    public function autoAssignForProject(int $projectId): array
    {
        $results = [];

        // Get all cabinets with drawers for this project
        $cabinets = DB::table('projects_cabinets as c')
            ->join('projects_cabinet_runs as cr', 'c.cabinet_run_id', '=', 'cr.id')
            ->join('projects_room_locations as rl', 'cr.room_location_id', '=', 'rl.id')
            ->join('projects_rooms as r', 'rl.room_id', '=', 'r.id')
            ->where('r.project_id', $projectId)
            ->where('c.drawer_count', '>', 0)
            ->select('c.id as cabinet_id', 'c.cabinet_number', 'c.drawer_count', 'c.depth_inches', 'r.id as room_id', 'r.name as room_name')
            ->get();

        foreach ($cabinets as $cabinet) {
            // Get drawer details if available, otherwise use cabinet depth
            $drawers = DB::table('projects_drawers')
                ->where('cabinet_id', $cabinet->cabinet_id)
                ->select('box_depth_inches')
                ->get();

            if ($drawers->isEmpty()) {
                // Use cabinet depth as default
                $drawerData = [['depth' => $cabinet->depth_inches ?? 18, 'count' => $cabinet->drawer_count]];
            } else {
                $drawerData = $drawers->map(fn($d) => ['depth' => $d->box_depth_inches ?? 18, 'count' => 1])->toArray();
            }

            // Check if hardware already assigned
            $existing = DB::table('hardware_requirements')
                ->where('cabinet_id', $cabinet->cabinet_id)
                ->where('hardware_type', 'slide')
                ->exists();

            if (!$existing) {
                $hardware = $this->createHardwareRequirements(
                    $cabinet->cabinet_id,
                    $cabinet->room_id,
                    $drawerData
                );

                $results[] = [
                    'cabinet_id' => $cabinet->cabinet_id,
                    'cabinet_number' => $cabinet->cabinet_number,
                    'room' => $cabinet->room_name,
                    'drawer_count' => $cabinet->drawer_count,
                    'hardware_assigned' => $hardware['summary'],
                    'status' => 'created',
                ];
            } else {
                $results[] = [
                    'cabinet_id' => $cabinet->cabinet_id,
                    'cabinet_number' => $cabinet->cabinet_number,
                    'status' => 'skipped (already assigned)',
                ];
            }
        }

        return $results;
    }
}
