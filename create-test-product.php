<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Product\Models\Product;
use Webkul\Product\Models\Attribute;
use Webkul\Product\Models\ProductAttribute;
use Webkul\Product\Models\ProductAttributeValue;
use Webkul\Product\Enums\ProductType;

echo "=== Creating Test Drawer Slide Product ===\n\n";

// Create or find test drawer slide product
$product = Product::firstOrCreate(
    ['reference' => 'BLUM-LEGRABOX-21'],
    [
        'name' => 'Blum LEGRABOX 21" Drawer Slide',
        'type' => ProductType::GOODS,
        'price' => 85.00,
        'cost' => 55.00,
        'uom_id' => 1,
        'uom_po_id' => 1,
        'category_id' => 58, // Hardware category
        'enable_sales' => true,
        'enable_purchase' => true,
        'description' => 'Blum LEGRABOX 21-inch drawer slide with integrated soft-close',
        'creator_id' => 1,
    ]
);
echo "Product ID: {$product->id} - {$product->name}\n";

// Get attributes
$slideLength = Attribute::where('name', 'Slide Length')->first();
$clearance = Attribute::where('name', 'Total Width Clearance')->first();
$depthOffset = Attribute::where('name', 'Depth Offset')->first();

if (!$slideLength || !$clearance || !$depthOffset) {
    echo "Error: Attributes not found!\n";
    exit(1);
}

// Add Slide Length attribute (21 inches)
$pa1 = ProductAttribute::firstOrCreate([
    'product_id' => $product->id,
    'attribute_id' => $slideLength->id,
], ['creator_id' => 1]);

ProductAttributeValue::updateOrCreate(
    ['product_attribute_id' => $pa1->id, 'product_id' => $product->id],
    [
        'attribute_id' => $slideLength->id,
        'numeric_value' => 21.0,
        'extra_price' => 0,
    ]
);
echo "Added Slide Length: 21 in\n";

// Add Total Width Clearance (35mm for LEGRABOX)
$pa2 = ProductAttribute::firstOrCreate([
    'product_id' => $product->id,
    'attribute_id' => $clearance->id,
], ['creator_id' => 1]);

ProductAttributeValue::updateOrCreate(
    ['product_attribute_id' => $pa2->id, 'product_id' => $product->id],
    [
        'attribute_id' => $clearance->id,
        'numeric_value' => 35.0,
        'extra_price' => 0,
    ]
);
echo "Added Total Width Clearance: 35 mm\n";

// Add Depth Offset (10mm)
$pa3 = ProductAttribute::firstOrCreate([
    'product_id' => $product->id,
    'attribute_id' => $depthOffset->id,
], ['creator_id' => 1]);

ProductAttributeValue::updateOrCreate(
    ['product_attribute_id' => $pa3->id, 'product_id' => $product->id],
    [
        'attribute_id' => $depthOffset->id,
        'numeric_value' => 10.0,
        'extra_price' => 0,
    ]
);
echo "Added Depth Offset: 10 mm\n";

// Verify specs
echo "\nVerifying getNumericSpecifications()...\n";
$specs = $product->fresh()->getNumericSpecifications();
foreach ($specs as $name => $data) {
    echo "  $name: {$data['formatted']}\n";
}

echo "\n=== Test product created successfully! ===\n";
echo "\nExpected auto-calculations:\n";
echo "  - Opening width 24\" with 35mm clearance => Drawer width: " . round(24 - (35/25.4), 4) . "\"\n";
echo "  - Slide length 21\" with 10mm offset => Drawer depth: " . round(21 - (10/25.4), 4) . "\"\n";
