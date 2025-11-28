<?php

namespace Webkul\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * TCS Sample Data Seeder
 *
 * Seeds comprehensive sample data across all TCS-related tables:
 * - Inventory products (wood materials)
 * - Material inventory mappings (updated with product IDs)
 * - Sample project with rooms, locations, runs, and cabinets
 */
class TcsSampleDataSeeder extends Seeder
{
    protected $now;
    protected $productIds = [];

    public function run(): void
    {
        $this->now = Carbon::now();

        echo "\n=== TCS Sample Data Seeder ===\n\n";

        $this->seedInventoryProducts();
        $this->updateMaterialMappings();
        $this->seedSampleProject();

        echo "\n=== Seeding Complete ===\n\n";
    }

    /**
     * Seed inventory products for all wood materials
     */
    protected function seedInventoryProducts(): void
    {
        echo "1. Seeding inventory products...\n";

        // Get UOM IDs
        $boardFeetUomId = DB::table('unit_of_measures')->where('name', 'Linear Foot')->value('id') ?? 27;
        $squareFeetUomId = DB::table('unit_of_measures')->where('name', 'Square Foot')->value('id') ?? 28;
        $unitsUomId = DB::table('unit_of_measures')->where('name', 'Units')->value('id') ?? 1;

        // Get product category - use "Home Construction" or create "Raw Materials"
        $categoryId = DB::table('products_categories')->where('name', 'Home Construction')->value('id') ?? 4;

        $products = [
            // Paint Grade Materials
            [
                'name' => 'Hard Maple Lumber - 4/4 S2S',
                'reference' => 'WOOD-MAPLE-HARD-44',
                'type' => 'goods', // ProductType::GOODS
                'cost' => 8.50,
                'price' => 12.75,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'Paint grade hard maple lumber, 4/4 thickness, surfaced 2 sides. Primary material for paint grade cabinets.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],
            [
                'name' => 'Poplar Lumber - 4/4 S2S',
                'reference' => 'WOOD-POPLAR-44',
                'type' => 'goods',
                'cost' => 6.25,
                'price' => 9.50,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'Paint grade poplar lumber, 4/4 thickness, surfaced 2 sides. Cost-effective paint grade option.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],
            [
                'name' => 'Birch Plywood - 3/4" A2',
                'reference' => 'PLYWOOD-BIRCH-34-A2',
                'type' => 'goods',
                'cost' => 65.00,
                'price' => 95.00,
                'uom_id' => $unitsUomId, // Sheets sold as units
                'uom_po_id' => $unitsUomId,
                'category_id' => $categoryId,
                'description' => 'Birch plywood, 3/4" thickness, A2 grade, 4x8 sheets. Sheet good for paint grade cabinet boxes.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],

            // Stain Grade Materials
            [
                'name' => 'Red Oak Lumber - 4/4 Select',
                'reference' => 'WOOD-OAK-RED-44-SEL',
                'type' => 'goods',
                'cost' => 9.75,
                'price' => 14.50,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'Red oak lumber, 4/4 thickness, select grade. Classic stain grade hardwood.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],
            [
                'name' => 'White Oak Lumber - 4/4 Select',
                'reference' => 'WOOD-OAK-WHITE-44-SEL',
                'type' => 'goods',
                'cost' => 11.25,
                'price' => 16.75,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'White oak lumber, 4/4 thickness, select grade. Premium stain grade hardwood.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],
            [
                'name' => 'Hard Maple Lumber - 4/4 Select (Stain)',
                'reference' => 'WOOD-MAPLE-HARD-44-STAIN',
                'type' => 'goods',
                'cost' => 10.50,
                'price' => 15.75,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'Hard maple lumber, 4/4 thickness, select grade for staining. Stain grade maple - higher select than paint grade.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],

            // Premium Materials
            [
                'name' => 'Rift White Oak Lumber - 4/4 Premium',
                'reference' => 'WOOD-OAK-RIFT-44-PREM',
                'type' => 'goods',
                'cost' => 18.50,
                'price' => 27.75,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'Rift sawn white oak lumber, 4/4 thickness, premium grade. Premium straight grain pattern.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],
            [
                'name' => 'Black Walnut Lumber - 4/4 Select',
                'reference' => 'WOOD-WALNUT-BLACK-44-SEL',
                'type' => 'goods',
                'cost' => 22.00,
                'price' => 33.00,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'Black walnut lumber, 4/4 thickness, select grade. Premium dark hardwood.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],
            [
                'name' => 'Cherry Lumber - 4/4 Select',
                'reference' => 'WOOD-CHERRY-44-SEL',
                'type' => 'goods',
                'cost' => 15.75,
                'price' => 23.50,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'Cherry lumber, 4/4 thickness, select grade. Premium reddish hardwood.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],

            // Custom/Exotic
            [
                'name' => 'Exotic Hardwood - Various Species',
                'reference' => 'WOOD-EXOTIC-CUSTOM',
                'type' => 'goods',
                'cost' => 25.00,
                'price' => 40.00,
                'uom_id' => $boardFeetUomId,
                'uom_po_id' => $boardFeetUomId,
                'category_id' => $categoryId,
                'description' => 'Exotic hardwoods - mahogany, teak, etc. Custom pricing per species.',
                'enable_sales' => true,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'qty',
            ],
        ];

        foreach ($products as $product) {
            $id = DB::table('products_products')->insertGetId(array_merge($product, [
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));

            $this->productIds[$product['reference']] = $id;
            echo "  ✓ Created product: {$product['name']} (ID: {$id})\n";
        }
    }

    /**
     * Update material mappings with inventory product IDs
     */
    protected function updateMaterialMappings(): void
    {
        echo "\n2. Updating material mappings with product IDs...\n";

        $mappings = [
            // Paint Grade
            ['species' => 'Hard Maple', 'reference' => 'WOOD-MAPLE-HARD-44'],
            ['species' => 'Poplar', 'reference' => 'WOOD-POPLAR-44'],
            ['species' => 'Birch Plywood', 'reference' => 'PLYWOOD-BIRCH-34-A2'],

            // Stain Grade
            ['species' => 'Red Oak', 'reference' => 'WOOD-OAK-RED-44-SEL'],
            ['species' => 'White Oak', 'reference' => 'WOOD-OAK-WHITE-44-SEL'],
            ['species' => 'Hard Maple (Stain)', 'reference' => 'WOOD-MAPLE-HARD-44-STAIN'],

            // Premium
            ['species' => 'Rifted White Oak', 'reference' => 'WOOD-OAK-RIFT-44-PREM'],
            ['species' => 'Black Walnut', 'reference' => 'WOOD-WALNUT-BLACK-44-SEL'],
            ['species' => 'Cherry', 'reference' => 'WOOD-CHERRY-44-SEL'],

            // Custom
            ['species' => 'Exotic/Custom Wood', 'reference' => 'WOOD-EXOTIC-CUSTOM'],
        ];

        foreach ($mappings as $mapping) {
            if (isset($this->productIds[$mapping['reference']])) {
                DB::table('tcs_material_inventory_mappings')
                    ->where('wood_species', $mapping['species'])
                    ->update([
                        'inventory_product_id' => $this->productIds[$mapping['reference']],
                        'updated_at' => $this->now,
                    ]);

                echo "  ✓ Linked {$mapping['species']} → {$mapping['reference']}\n";
            }
        }
    }

    /**
     * Seed sample project with rooms, locations, runs, and cabinets
     */
    protected function seedSampleProject(): void
    {
        echo "\n3. Seeding sample project...\n";

        // Create sample project
        $projectId = DB::table('projects_projects')->insertGetId([
            'name' => 'TCS Sample Kitchen Renovation',
            'description' => 'Sample project demonstrating TCS cabinet pricing and material BOM system',
            'start_date' => $this->now->toDateString(),
            'is_active' => true,
            'allow_timesheets' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        echo "  ✓ Created project: TCS Sample Kitchen Renovation (ID: {$projectId})\n";

        // Create Kitchen room with Stain Grade material
        $kitchenRoomId = DB::table('projects_rooms')->insertGetId([
            'project_id' => $projectId,
            'name' => 'Main Kitchen',
            'room_type' => 'kitchen',
            'cabinet_level' => '3',
            'material_category' => 'stain_grade',
            'finish_option' => 'natural_stain',
            'notes' => 'Main kitchen - red oak stain grade cabinets',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        echo "  ✓ Created room: Main Kitchen (Stain Grade)\n";

        // Create Butler's Pantry with Premium material
        $pantryRoomId = DB::table('projects_rooms')->insertGetId([
            'project_id' => $projectId,
            'name' => "Butler's Pantry",
            'room_type' => 'pantry',
            'cabinet_level' => '4',
            'material_category' => 'premium',
            'finish_option' => 'custom_stain',
            'notes' => "Butler's pantry - black walnut premium cabinets",
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        echo "  ✓ Created room: Butler's Pantry (Premium)\n";

        // Create locations
        $mainWallLocationId = DB::table('projects_room_locations')->insertGetId([
            'room_id' => $kitchenRoomId,
            'name' => 'Main Wall - North',
            'location_type' => 'wall',
            'notes' => 'Primary cooking wall with sink and range',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $islandLocationId = DB::table('projects_room_locations')->insertGetId([
            'room_id' => $kitchenRoomId,
            'name' => 'Center Island',
            'location_type' => 'island',
            'notes' => 'Large center island with seating',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $pantryLocationId = DB::table('projects_room_locations')->insertGetId([
            'room_id' => $pantryRoomId,
            'name' => 'Pantry Storage Wall',
            'location_type' => 'wall',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        echo "  ✓ Created 3 room locations\n";

        // Create cabinet runs
        $baseRunId = DB::table('projects_cabinet_runs')->insertGetId([
            'room_location_id' => $mainWallLocationId,
            'name' => 'Base Cabinet Run',
            'run_type' => 'base',
            'total_linear_feet' => 18.0,
            'start_wall_measurement' => 0,
            'end_wall_measurement' => 216, // 18 feet in inches
            'notes' => 'Base cabinets along main wall',
            'sort_order' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $wallRunId = DB::table('projects_cabinet_runs')->insertGetId([
            'room_location_id' => $mainWallLocationId,
            'name' => 'Wall Cabinet Run',
            'run_type' => 'wall',
            'total_linear_feet' => 15.0,
            'start_wall_measurement' => 0,
            'end_wall_measurement' => 180, // 15 feet in inches
            'notes' => 'Wall cabinets above base run',
            'sort_order' => 2,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $islandRunId = DB::table('projects_cabinet_runs')->insertGetId([
            'room_location_id' => $islandLocationId,
            'name' => 'Island Base Run',
            'run_type' => 'base',
            'total_linear_feet' => 8.0,
            'notes' => 'Island base cabinets',
            'sort_order' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $tallRunId = DB::table('projects_cabinet_runs')->insertGetId([
            'room_location_id' => $pantryLocationId,
            'name' => 'Tall Pantry Run',
            'run_type' => 'tall',
            'total_linear_feet' => 6.0,
            'material_category' => 'premium', // Override to use premium walnut
            'notes' => 'Tall pantry cabinets - premium walnut',
            'sort_order' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        echo "  ✓ Created 4 cabinet runs\n";

        // Create cabinet specifications
        $this->seedCabinets($baseRunId, $kitchenRoomId, 'base');
        $this->seedCabinets($wallRunId, $kitchenRoomId, 'wall');
        $this->seedCabinets($islandRunId, $kitchenRoomId, 'island');
        $this->seedCabinets($tallRunId, $pantryRoomId, 'tall');
    }

    /**
     * Seed cabinet specifications for a run
     */
    protected function seedCabinets(int $runId, int $roomId, string $type): void
    {
        $cabinets = match ($type) {
            'base' => [
                ['number' => 'B1', 'length' => 18, 'depth' => 24, 'height' => 30, 'position' => 1],
                ['number' => 'B2', 'length' => 24, 'depth' => 24, 'height' => 30, 'position' => 2],
                ['number' => 'B3', 'length' => 36, 'depth' => 24, 'height' => 30, 'position' => 3], // Sink base
                ['number' => 'B4', 'length' => 30, 'depth' => 24, 'height' => 30, 'position' => 4],
                ['number' => 'B5', 'length' => 24, 'depth' => 24, 'height' => 30, 'position' => 5],
                ['number' => 'B6', 'length' => 36, 'depth' => 24, 'height' => 30, 'position' => 6],
                ['number' => 'B7', 'length' => 24, 'depth' => 24, 'height' => 30, 'position' => 7],
                ['number' => 'B8', 'length' => 24, 'depth' => 24, 'height' => 30, 'position' => 8],
            ],
            'wall' => [
                ['number' => 'W1', 'length' => 30, 'depth' => 12, 'height' => 30, 'position' => 1],
                ['number' => 'W2', 'length' => 36, 'depth' => 12, 'height' => 30, 'position' => 2],
                ['number' => 'W3', 'length' => 30, 'depth' => 12, 'height' => 30, 'position' => 3],
                ['number' => 'W4', 'length' => 24, 'depth' => 12, 'height' => 30, 'position' => 4],
                ['number' => 'W5', 'length' => 36, 'depth' => 12, 'height' => 30, 'position' => 5],
            ],
            'island' => [
                ['number' => 'I1', 'length' => 36, 'depth' => 24, 'height' => 30, 'position' => 1],
                ['number' => 'I2', 'length' => 24, 'depth' => 24, 'height' => 30, 'position' => 2],
                ['number' => 'I3', 'length' => 36, 'depth' => 24, 'height' => 30, 'position' => 3],
            ],
            'tall' => [
                ['number' => 'T1', 'length' => 18, 'depth' => 24, 'height' => 84, 'position' => 1],
                ['number' => 'T2', 'length' => 24, 'depth' => 24, 'height' => 96, 'position' => 2],
                ['number' => 'T3', 'length' => 30, 'depth' => 24, 'height' => 84, 'position' => 3],
            ],
            default => [],
        };

        $count = 0;
        foreach ($cabinets as $cabinet) {
            $linearFeet = round($cabinet['length'] / 12, 2);

            // Get base pricing (Level 3 for kitchen, Level 4 for pantry)
            $cabinetLevel = $type === 'tall' ? '4' : '3';
            $materialCategory = $type === 'tall' ? 'premium' : 'stain_grade';
            $finishOption = $type === 'tall' ? 'custom_stain' : 'natural_stain';

            // Calculate unit price using TCS pricing
            $unitPrice = $this->calculateTcsPrice($cabinetLevel, $materialCategory, $finishOption);
            $totalPrice = round($unitPrice * $linearFeet, 2);

            DB::table('projects_cabinet_specifications')->insert([
                'cabinet_run_id' => $runId,
                'room_id' => $roomId,
                'cabinet_number' => $cabinet['number'],
                'position_in_run' => $cabinet['position'],
                'length_inches' => $cabinet['length'],
                'depth_inches' => $cabinet['depth'],
                'height_inches' => $cabinet['height'],
                'linear_feet' => $linearFeet,
                'quantity' => 1,
                'cabinet_level' => $cabinetLevel,
                'material_category' => $materialCategory,
                'finish_option' => $finishOption,
                'unit_price_per_lf' => $unitPrice,
                'total_price' => $totalPrice,
                'shop_notes' => "Sample {$type} cabinet - {$cabinet['length']}\" wide",
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);

            $count++;
        }

        echo "    ✓ Created {$count} {$type} cabinets\n";
    }

    /**
     * Calculate TCS pricing (simplified version matching service)
     */
    protected function calculateTcsPrice(string $level, string $material, string $finish): float
    {
        $basePrices = [
            '1' => 138.00,
            '2' => 168.00,
            '3' => 192.00,
            '4' => 210.00,
            '5' => 225.00,
        ];

        $materialPrices = [
            'paint_grade' => 138.00,
            'stain_grade' => 156.00,
            'premium' => 185.00,
            'custom_exotic' => 0.00,
        ];

        $finishPrices = [
            'unfinished' => 0.00,
            'natural_stain' => 65.00,
            'custom_stain' => 125.00,
        ];

        $basePrice = $basePrices[$level] ?? 192.00;
        $materialPrice = $materialPrices[$material] ?? 156.00;
        $finishPrice = $finishPrices[$finish] ?? 0.00;

        return $basePrice + $materialPrice + $finishPrice;
    }
}
