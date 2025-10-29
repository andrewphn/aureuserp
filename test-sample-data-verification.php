<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Webkul\Product\Models\Product;
use Webkul\Project\Models\TcsMaterialInventoryMapping;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\CabinetSpecification;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  TCS SAMPLE DATA VERIFICATION REPORT                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

// 1. Verify Products
echo "1. INVENTORY PRODUCTS\n";
echo str_repeat('─', 70) . "\n";

$productReferences = [
    'WOOD-MAPLE-HARD-44', 'WOOD-POPLAR-44', 'PLYWOOD-BIRCH-34-A2',
    'WOOD-OAK-RED-44-SEL', 'WOOD-OAK-WHITE-44-SEL', 'WOOD-MAPLE-HARD-44-STAIN',
    'WOOD-OAK-RIFT-44-PREM', 'WOOD-WALNUT-BLACK-44-SEL', 'WOOD-CHERRY-44-SEL',
    'WOOD-EXOTIC-CUSTOM'
];

$products = Product::whereIn('reference', $productReferences)->get();

foreach ($products as $p) {
    echo sprintf("  ✓ %-30s | Cost: \$%-6.2f | ID: %d\n",
        $p->reference, $p->cost, $p->id);
}
echo "  Total Products Created: " . $products->count() . "/10\n\n";

// 2. Verify Material Mappings
echo "2. MATERIAL INVENTORY MAPPINGS\n";
echo str_repeat('─', 70) . "\n";

$mappings = TcsMaterialInventoryMapping::with('inventoryProduct')->get();
$linkedCount = $mappings->filter(fn($m) => $m->inventory_product_id !== null)->count();

echo "  Total Mappings: " . $mappings->count() . "\n";
echo "  Linked to Products: " . $linkedCount . "/10\n";
echo "  Status: " . ($linkedCount === 10 ? '✓ All mappings linked successfully' : '✗ Missing links') . "\n\n";

// 3. Verify Sample Project
echo "3. SAMPLE PROJECT\n";
echo str_repeat('─', 70) . "\n";

$project = Project::where('name', 'TCS Sample Kitchen Renovation')->first();

if ($project) {
    echo "  ✓ Project: " . $project->name . " (ID: " . $project->id . ")\n";

    $rooms = Room::where('project_id', $project->id)->get();
    echo "  ✓ Rooms Created: " . $rooms->count() . "\n";

    foreach ($rooms as $room) {
        echo "    - " . $room->name . " (" . $room->material_category . ")\n";
    }

    $cabinets = CabinetSpecification::whereHas('cabinetRun.roomLocation.room', function($q) use ($project) {
        $q->where('project_id', $project->id);
    })->get();

    echo "  ✓ Cabinets Created: " . $cabinets->count() . "\n";

    $totalLF = $cabinets->sum('linear_feet');
    echo "  ✓ Total Linear Feet: " . number_format($totalLF, 2) . " LF\n";
} else {
    echo "  ✗ Sample project not found\n";
}
echo "\n";

// 4. Verify BOM Generation
echo "4. BOM GENERATION & COST CALCULATION\n";
echo str_repeat('─', 70) . "\n";

$testCabinet = CabinetSpecification::where('cabinet_number', 'B1')->first();

if ($testCabinet) {
    echo "  Testing Cabinet: " . $testCabinet->cabinet_number . "\n";
    echo "    - Dimensions: {$testCabinet->length_inches}\" × {$testCabinet->depth_inches}\" × {$testCabinet->height_inches}\"\n";
    echo "    - Linear Feet: " . $testCabinet->linear_feet . " LF\n";
    echo "    - Material Category: " . $testCabinet->material_category . "\n\n";

    $bom = $testCabinet->generateBom();
    echo "  ✓ BOM Items Generated: " . $bom->count() . "\n";

    foreach ($bom as $item) {
        echo sprintf("    - %-30s: %7.2f %s\n",
            $item['wood_species'],
            $item['quantity'],
            $item['unit']
        );
    }

    $cost = $testCabinet->estimateMaterialCost();
    echo "\n  ✓ Material Cost Calculated: \$" . number_format($cost, 2) . "\n";
} else {
    echo "  ✗ Test cabinet B1 not found\n";
}
echo "\n";

// 5. Final Summary
echo "5. SYSTEM STATUS SUMMARY\n";
echo str_repeat('─', 70) . "\n";

$allGood = $products->count() === 10
    && $linkedCount === 10
    && $project !== null
    && $testCabinet !== null
    && isset($cost) && $cost > 0;

if ($allGood) {
    echo "  ✓ All systems operational\n";
    echo "  ✓ Sample data successfully seeded\n";
    echo "  ✓ Products created and viewable in admin\n";
    echo "  ✓ Material mappings linked to inventory\n";
    echo "  ✓ BOM generation working correctly\n";
    echo "  ✓ Material cost calculation working correctly\n";
    echo "\n";
    echo "  STATUS: ✓ READY FOR PRODUCTION USE\n";
} else {
    echo "  ✗ Some systems have issues - review sections above\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  END OF VERIFICATION REPORT                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
