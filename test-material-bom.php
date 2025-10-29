<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\TcsMaterialInventoryMapping;
use Webkul\Project\Services\MaterialBomService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TCS Material Inventory Mapping Test ===\n\n";

// Test 1: Check material mappings
echo "1. Material Mappings in Database:\n";
echo str_repeat('-', 80) . "\n";

$mappings = TcsMaterialInventoryMapping::active()
    ->byPriority()
    ->get();

foreach ($mappings as $mapping) {
    echo sprintf(
        "  • %s - %s (%s)\n",
        $mapping->getTcsMaterialDisplayAttribute(),
        $mapping->wood_species,
        $mapping->material_type
    );
    echo sprintf(
        "    Usage: %s | Priority: %d\n",
        $mapping->usage_description,
        $mapping->priority
    );

    if ($mapping->board_feet_per_lf > 0) {
        echo sprintf("    Board Feet/LF: %.2f\n", $mapping->board_feet_per_lf);
    }
    if ($mapping->sheet_sqft_per_lf > 0) {
        echo sprintf("    Square Feet/LF: %.2f\n", $mapping->sheet_sqft_per_lf);
    }
    echo "\n";
}

// Test 2: Test BOM generation for a sample cabinet
echo "\n2. Sample Cabinet BOM Generation:\n";
echo str_repeat('-', 80) . "\n";

// Find a cabinet with material category set
$cabinet = CabinetSpecification::whereNotNull('material_category')
    ->first();

if ($cabinet) {
    echo "Cabinet: {$cabinet->cabinet_number}\n";
    echo "Dimensions: {$cabinet->length_inches}\" × {$cabinet->depth_inches}\" × {$cabinet->height_inches}\"\n";
    echo "Linear Feet: {$cabinet->linear_feet}\n";
    echo "Quantity: {$cabinet->quantity}\n";
    echo "Material Category: " . ($cabinet->material_category ?? 'None') . "\n\n";

    echo "Generated BOM:\n";
    $bom = $cabinet->generateBom();

    if ($bom->isEmpty()) {
        echo "  (No BOM items generated - check material category assignment)\n";
    } else {
        foreach ($bom as $item) {
            echo sprintf(
                "  • %s: %.2f %s\n",
                $item['wood_species'],
                $item['quantity'],
                $item['unit']
            );
            echo sprintf(
                "    Usage: %s | Priority: %d\n",
                $item['is_box_material'] ? 'Box' : '',
                $item['priority'] ?? 0
            );
        }

        $materialCost = $cabinet->estimateMaterialCost();
        echo sprintf("\nEstimated Material Cost: $%.2f\n", $materialCost);
    }
} else {
    echo "  (No cabinets with material category found)\n";
    echo "  Creating test data...\n\n";

    // Create a test cabinet
    $testCabinet = new CabinetSpecification([
        'cabinet_number' => 'TEST-BOM-001',
        'length_inches' => 36,
        'depth_inches' => 24,
        'height_inches' => 30,
        'linear_feet' => 3.0,
        'quantity' => 1,
        'material_category' => 'stain_grade', // Red Oak
        'cabinet_level' => '3',
        'finish_option' => 'unfinished',
    ]);

    echo "Test Cabinet Created:\n";
    echo "  Cabinet: {$testCabinet->cabinet_number}\n";
    echo "  Dimensions: 36\" × 24\" × 30\"\n";
    echo "  Linear Feet: 3.0\n";
    echo "  Material: Stain Grade\n\n";

    $bomService = new MaterialBomService();
    $bom = $bomService->generateBomForCabinet($testCabinet);

    echo "Generated BOM:\n";
    foreach ($bom as $item) {
        echo sprintf(
            "  • %s: %.2f %s\n",
            $item['wood_species'],
            $item['quantity'],
            $item['unit']
        );
    }
}

// Test 3: Material recommendations
echo "\n3. Material Recommendations:\n";
echo str_repeat('-', 80) . "\n";

foreach (['paint_grade', 'stain_grade', 'premium'] as $category) {
    echo "\n{$category}:\n";

    $recommendations = TcsMaterialInventoryMapping::forMaterial($category)
        ->active()
        ->byPriority()
        ->limit(3)
        ->get();

    foreach ($recommendations as $rec) {
        echo sprintf(
            "  %d. %s (%s) - %s\n",
            $rec->priority,
            $rec->wood_species,
            $rec->material_type,
            $rec->usage_description
        );
    }
}

// Test 4: Calculate requirements for different linear footages
echo "\n\n4. Material Requirements Calculator:\n";
echo str_repeat('-', 80) . "\n";

$testMapping = TcsMaterialInventoryMapping::forMaterial('stain_grade')
    ->where('wood_species', 'Red Oak')
    ->first();

if ($testMapping) {
    echo "Material: Red Oak (Stain Grade)\n";
    echo "Usage multiplier: {$testMapping->board_feet_per_lf} board feet per LF\n\n";

    foreach ([1, 3, 5, 10, 20] as $lf) {
        $req = $testMapping->calculateRequirement($lf, true);
        echo sprintf(
            "  %2d LF → %.2f %s (with 10%% waste)\n",
            $lf,
            $req['quantity'],
            $req['unit_display']
        );
    }
}

echo "\n=== Test Complete ===\n";
