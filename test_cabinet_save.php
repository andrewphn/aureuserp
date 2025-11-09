<?php

// Test script to verify cabinet data is being passed correctly

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test data that should be passed to createEntity
$testEntityData = [
    'cabinet_number' => 'TEST-1',
    'position_in_run' => 1,
    'length_inches' => 30.5,
    'width_inches' => 12.0,
    'depth_inches' => 24.0,
    'height_inches' => 84.0,
    'linear_feet' => 2.54,
    'quantity' => 1,
    'unit_price_per_lf' => 450.00,
    'cabinet_level' => '4',
    'material_category' => 'premium',
    'finish_option' => 'stain_clear',
];

echo "Test Entity Data:\n";
print_r($testEntityData);

// Check which fields are in fillable
$cabinet = new \Webkul\Project\Models\CabinetSpecification();
$fillable = $cabinet->getFillable();

echo "\n\nFillable fields:\n";
print_r($fillable);

echo "\n\nChecking which test fields are fillable:\n";
foreach ($testEntityData as $field => $value) {
    $isFillable = in_array($field, $fillable);
    echo "  $field: " . ($isFillable ? "✓ YES" : "✗ NO") . "\n";
}
