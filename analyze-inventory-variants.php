<?php

/**
 * Inventory Variant Candidate Detection Script
 *
 * Analyzes inventory_complete_final.csv to identify products
 * that should be imported as variants rather than separate products.
 *
 * Usage: php analyze-inventory-variants.php
 */

$csvPath = __DIR__ . '/inventory_import_ready.csv';

if (!file_exists($csvPath)) {
    echo "ERROR: CSV file not found at: {$csvPath}\n";
    exit(1);
}

// Parse CSV
$csvData = array_map('str_getcsv', file($csvPath));
$headers = array_shift($csvData); // Remove header row

// Find column indices
$columnMap = [];
foreach ($headers as $index => $header) {
    $columnMap[$header] = $index;
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║           INVENTORY VARIANT CANDIDATE DETECTION ANALYSIS                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Track unique products
$products = [];

foreach ($csvData as $row) {
    $sku = $row[$columnMap['sku']] ?? '';
    $name = $row[$columnMap['name']] ?? '';
    $category = $row[$columnMap['category']] ?? '';
    $quantity = parseDecimal($row[$columnMap['quantity_on_hand']] ?? '0');
    $unitCost = parseDecimal($row[$columnMap['cost_per_unit']] ?? '0');

    // Skip if no SKU
    if (empty($sku)) {
        continue;
    }

    $products[] = [
        'item_id' => $sku,
        'name' => $name,
        'category' => $category,
        'quantity' => $quantity,
        'unit_cost' => $unitCost,
        'base_name' => extractBaseName($name),
        'attributes' => extractAttributes($name),
    ];
}

echo "Found " . count($products) . " unique products\n\n";

// Group products by base name
$variantGroups = [];
foreach ($products as $product) {
    $baseName = $product['base_name'];

    if (!isset($variantGroups[$baseName])) {
        $variantGroups[$baseName] = [];
    }

    $variantGroups[$baseName][] = $product;
}

// Filter to only groups with 2+ products
$variantGroups = array_filter($variantGroups, function($group) {
    return count($group) >= 2;
});

echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
echo "│ VARIANT CANDIDATES                                                      │\n";
echo "└─────────────────────────────────────────────────────────────────────────┘\n\n";

if (empty($variantGroups)) {
    echo "No variant candidates detected.\n\n";
} else {
    foreach ($variantGroups as $baseName => $group) {
        echo "Parent: " . substr($baseName, 0, 60) . (strlen($baseName) > 60 ? '...' : '') . "\n";
        echo "Variants: " . count($group) . "\n";

        // Determine common attribute
        $attribute = detectCommonAttribute($group);
        echo "Detected attribute: " . ($attribute ?: 'Unknown') . "\n";

        foreach ($group as $product) {
            $attrValue = $product['attributes'][$attribute] ?? 'N/A';
            echo "  - ID: {$product['item_id']} | Value: {$attrValue} | Qty: {$product['quantity']} | \${$product['unit_cost']}\n";
        }
        echo "\n";
    }
}

echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
echo "│ SUMMARY                                                                 │\n";
echo "└─────────────────────────────────────────────────────────────────────────┘\n\n";

echo "Total products: " . count($products) . "\n";
echo "Parent products: " . count($variantGroups) . "\n";
echo "Total variants: " . array_sum(array_map('count', $variantGroups)) . "\n";
echo "Standalone products: " . (count($products) - array_sum(array_map('count', $variantGroups))) . "\n";

// Generate JSON mapping
$mapping = [
    'generated_at' => date('Y-m-d H:i:s'),
    'source_file' => $csvPath,
    'variant_groups' => [],
];

foreach ($variantGroups as $baseName => $group) {
    $attribute = detectCommonAttribute($group);

    $variantGroup = [
        'parent_name' => $baseName,
        'attribute' => $attribute,
        'products' => [],
    ];

    foreach ($group as $product) {
        $variantGroup['products'][] = [
            'item_id' => $product['item_id'],
            'original_name' => $product['name'],
            'attribute_value' => $product['attributes'][$attribute] ?? null,
            'quantity' => $product['quantity'],
            'unit_cost' => $product['unit_cost'],
        ];
    }

    $mapping['variant_groups'][] = $variantGroup;
}

$outputPath = __DIR__ . '/inventory-variant-mapping.json';
file_put_contents($outputPath, json_encode($mapping, JSON_PRETTY_PRINT));

echo "\nVariant mapping saved to: {$outputPath}\n\n";

/**
 * Extract base product name by removing variant indicators
 */
function extractBaseName(string $title): string
{
    // Remove size indicators (common in CNC bits)
    $base = preg_replace('/\b\d+\/\d+["\']?\b/', '', $title); // e.g., "3/8"", "1/4""
    $base = preg_replace('/\(\d+\/\d+["\']?\s*[^)]*\)/', '', $base); // e.g., "(3/8" Down-cut)"

    // Remove grit indicators
    $base = preg_replace('/\b\d+\s*Grit\b/i', '', $base);

    // Remove size indicators
    $base = preg_replace('/\b\d+["\']?[-\s]?inch\b/i', '', $base);

    // Remove length indicators
    $base = preg_replace('/\b\d+["\']?\s+inch\b/i', '', $base);

    // Remove dimension patterns (e.g., "12 inch", "10 inch")
    $base = preg_replace('/\b\d+\s+inch\b/i', '', $base);

    // Clean up extra spaces
    $base = preg_replace('/\s+/', ' ', $base);
    $base = trim($base);

    return $base;
}

/**
 * Extract variant attributes from product title
 */
function extractAttributes(string $title): array
{
    $attributes = [];

    // Extract fractional sizes (e.g., "3/8"", "1/4"")
    if (preg_match('/\b(\d+\/\d+)["\']?\b/', $title, $matches)) {
        $attributes['Size'] = $matches[1] . '"';
    }

    // Extract grit
    if (preg_match('/\b(\d+)\s*Grit\b/i', $title, $matches)) {
        $attributes['Grit'] = $matches[1];
    }

    // Extract whole number sizes with inch
    if (preg_match('/\b(\d+)["\']?\s*[-]?inch\b/i', $title, $matches)) {
        $attributes['Size'] = $matches[1] . '"';
    }

    // Extract type indicators (e.g., "Down-cut", "Compression", "Up-cut")
    if (preg_match('/\b(Down-?cut|Up-?cut|Compression|Spiral)\b/i', $title, $matches)) {
        $attributes['Type'] = ucfirst(strtolower($matches[1]));
    }

    return $attributes;
}

/**
 * Detect the most common attribute across a variant group
 */
function detectCommonAttribute(array $group): ?string
{
    $attributeCounts = [];

    foreach ($group as $product) {
        foreach (array_keys($product['attributes']) as $attr) {
            $attributeCounts[$attr] = ($attributeCounts[$attr] ?? 0) + 1;
        }
    }

    if (empty($attributeCounts)) {
        return null;
    }

    // Return attribute that appears most frequently
    arsort($attributeCounts);
    return array_key_first($attributeCounts);
}

/**
 * Parse decimal value from CSV (removes quotes and commas)
 */
function parseDecimal(string $value): float
{
    $cleaned = str_replace(['"', ','], '', $value);
    return floatval($cleaned);
}
