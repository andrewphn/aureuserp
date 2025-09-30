<?php

/**
 * Variant Candidate Detection Script
 *
 * Analyzes Amazon orders CSV and migration spreadsheets to identify products
 * that should be imported as variants rather than separate products.
 *
 * Usage: php analyze-variant-candidates.php
 */

$csvPath = __DIR__ . '/orders_from_20250901_to_20250930_20250930_0935.csv';

if (!file_exists($csvPath)) {
    echo "ERROR: CSV file not found at: {$csvPath}\n";
    exit(1);
}

// Parse CSV
$csvData = array_map('str_getcsv', file($csvPath));
$headers = array_shift($csvData); // Remove header row

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║              VARIANT CANDIDATE DETECTION ANALYSIS                         ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Track unique products by ASIN
$products = [];
$processedAsins = [];

foreach ($csvData as $row) {
    $asin = $row[28] ?? '';
    $title = $row[29] ?? '';
    $brand = $row[36] ?? '';
    $itemPrice = parseDecimal($row[44] ?? '0');

    // Skip if no ASIN or already processed
    if (empty($asin) || in_array($asin, $processedAsins)) {
        continue;
    }

    $processedAsins[] = $asin;

    $products[] = [
        'asin' => $asin,
        'title' => $title,
        'brand' => $brand,
        'price' => $itemPrice,
        'base_name' => extractBaseName($title),
        'attributes' => extractAttributes($title),
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
            echo "  - ASIN: {$product['asin']} | Value: {$attrValue} | \${$product['price']}\n";
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
            'asin' => $product['asin'],
            'original_name' => $product['title'],
            'attribute_value' => $product['attributes'][$attribute] ?? null,
            'price' => $product['price'],
        ];
    }

    $mapping['variant_groups'][] = $variantGroup;
}

$outputPath = __DIR__ . '/variant-mapping.json';
file_put_contents($outputPath, json_encode($mapping, JSON_PRETTY_PRINT));

echo "\nVariant mapping saved to: {$outputPath}\n\n";

/**
 * Extract base product name by removing variant indicators
 */
function extractBaseName(string $title): string
{
    // Remove grit indicators
    $base = preg_replace('/\b\d+\s*Grit\b/i', '', $title);

    // Remove size indicators
    $base = preg_replace('/\b\d+[\"\']?[-\s]?inch\b/i', '', $base);
    $base = preg_replace('/\b\d+[\"\']?\b/', '', $base);

    // Remove pack size indicators
    $base = preg_replace('/\(\d+\s*P(cs?|ack)\)/i', '', $base);

    // Remove length indicators for bungee cords
    $base = preg_replace('/\b\d+[\"\']?\s*Real\b/i', 'Real', $base);

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

    // Extract grit
    if (preg_match('/\b(\d+)\s*Grit\b/i', $title, $matches)) {
        $attributes['Grit'] = $matches[1];
    }

    // Extract size
    if (preg_match('/\b(\d+)[\"\']?[-\s]?inch\b/i', $title, $matches)) {
        $attributes['Size'] = $matches[1] . '"';
    }

    // Extract length (for bungee cords, etc.)
    if (preg_match('/\b(\d+)[\"\']?\s+Real\s+Heavy\s+Duty/i', $title, $matches)) {
        $attributes['Length'] = $matches[1] . '"';
    }

    // Extract pack size
    if (preg_match('/\((\d+)\s*P(cs?|ack)\)/i', $title, $matches)) {
        $attributes['Pack Size'] = $matches[1] . '-pack';
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
