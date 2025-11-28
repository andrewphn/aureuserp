#!/usr/bin/env php
<?php

/**
 * Verification script for attribute field disabled states and tooltips
 *
 * This script verifies that:
 * 1. Tooltips are stored in the database (not hardcoded)
 * 2. The database has description data for all attribute options
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Verifying Attribute Implementation...\n\n";

// Check if description column exists
echo "1. Checking database schema...\n";
$descriptionColumnExists = \Illuminate\Support\Facades\Schema::hasColumn('products_attribute_options', 'description');

if ($descriptionColumnExists) {
    echo "   ✅ Description column exists in products_attribute_options table\n\n";
} else {
    echo "   ❌ Description column NOT found - migration may not have run\n\n";
    exit(1);
}

// Check for tooltips in database
echo "2. Checking tooltip data in database...\n";

$pricingLevels = \DB::table('products_attribute_options')
    ->join('products_attributes', 'products_attribute_options.attribute_id', '=', 'products_attributes.id')
    ->where('products_attributes.code', 'pricing_level')
    ->select('products_attribute_options.name', 'products_attribute_options.description')
    ->get();

if ($pricingLevels->count() > 0) {
    echo "   Found " . $pricingLevels->count() . " pricing levels:\n";
    foreach ($pricingLevels as $level) {
        $hasDescription = !empty($level->description);
        $icon = $hasDescription ? '✅' : '❌';
        $desc = $hasDescription ? substr($level->description, 0, 50) . '...' : 'NO DESCRIPTION';
        echo "   $icon {$level->name}: {$desc}\n";
    }
    echo "\n";
} else {
    echo "   ⚠️  No pricing levels found in database\n\n";
}

$materialCategories = \DB::table('products_attribute_options')
    ->join('products_attributes', 'products_attribute_options.attribute_id', '=', 'products_attributes.id')
    ->where('products_attributes.code', 'material_category')
    ->select('products_attribute_options.name', 'products_attribute_options.description')
    ->get();

if ($materialCategories->count() > 0) {
    echo "   Found " . $materialCategories->count() . " material categories:\n";
    foreach ($materialCategories as $material) {
        $hasDescription = !empty($material->description);
        $icon = $hasDescription ? '✅' : '❌';
        $desc = $hasDescription ? substr($material->description, 0, 50) . '...' : 'NO DESCRIPTION';
        echo "   $icon {$material->name}: {$desc}\n";
    }
    echo "\n";
} else {
    echo "   ⚠️  No material categories found in database\n\n";
}

$finishOptions = \DB::table('products_attribute_options')
    ->join('products_attributes', 'products_attribute_options.attribute_id', '=', 'products_attributes.id')
    ->where('products_attributes.code', 'finish_option')
    ->select('products_attribute_options.name', 'products_attribute_options.description')
    ->get();

if ($finishOptions->count() > 0) {
    echo "   Found " . $finishOptions->count() . " finish options:\n";
    foreach ($finishOptions as $finish) {
        $hasDescription = !empty($finish->description);
        $icon = $hasDescription ? '✅' : '❌';
        $desc = $hasDescription ? substr($finish->description, 0, 50) . '...' : 'NO DESCRIPTION';
        echo "   $icon {$finish->name}: {$desc}\n";
    }
    echo "\n";
} else {
    echo "   ⚠️  No finish options found in database\n\n";
}

// Verify code implementation
echo "3. Verifying QuotationResource implementation...\n";
$resourceFile = __DIR__ . '/plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource.php';

if (file_exists($resourceFile)) {
    $content = file_get_contents($resourceFile);

    $hasDisabledLogic = strpos($content, '->disabled(function (Get $get)') !== false;
    $hasHintLogic = strpos($content, '->hint(function (Get $get)') !== false;
    $hasTooltipPlacement = strpos($content, 'x-tooltip.placement.bottom') !== false;
    $hasHintIcon = strpos($content, '->hintIcon(') !== false;

    echo "   " . ($hasDisabledLogic ? '✅' : '❌') . " Disabled state logic present\n";
    echo "   " . ($hasHintLogic ? '✅' : '❌') . " Hint/tooltip logic present\n";
    echo "   " . ($hasTooltipPlacement ? '✅' : '❌') . " Tooltip positioning (bottom) present\n";
    echo "   " . ($hasHintIcon ? '✅' : '❌') . " Hint icon present\n\n";

    if ($hasDisabledLogic && $hasHintLogic && $hasTooltipPlacement && $hasHintIcon) {
        echo "✅ Implementation verified successfully!\n\n";
        echo "To test manually:\n";
        echo "1. Navigate to: http://aureuserp.test/admin/sale/orders/quotations/create\n";
        echo "2. Without selecting a product - attribute fields should be disabled (greyed out)\n";
        echo "3. Select 'Cabinet' product - attribute fields should become enabled\n";
        echo "4. Hover over ℹ️ icon next to attribute fields - tooltip should appear BELOW\n";
        echo "5. Tooltip text should match descriptions from database shown above\n";
    } else {
        echo "❌ Implementation incomplete - some features missing\n";
    }
} else {
    echo "   ❌ QuotationResource file not found\n";
}
