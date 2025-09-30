<?php

/**
 * Product Name Cleanup Analysis
 *
 * Analyzes all products to extract:
 * - Brand
 * - Simplified product name
 * - Product attributes (size, grit, length, etc.)
 *
 * Usage: php analyze-product-names-cleanup.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║                 PRODUCT NAME CLEANUP ANALYSIS                             ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Get all products
$products = DB::table('products_products')
    ->whereNull('parent_id')
    ->orderBy('reference')
    ->get(['id', 'name', 'reference', 'price']);

echo "Analyzing " . count($products) . " parent/standalone products...\n\n";

$cleanup = [];

foreach ($products as $product) {
    $analysis = analyzeProduct($product->name, $product->reference);

    $cleanup[] = [
        'id' => $product->id,
        'original_name' => $product->name,
        'sku' => $product->reference,
        'brand' => $analysis['brand'],
        'simplified_name' => $analysis['simplified_name'],
        'attributes' => $analysis['attributes'],
        'needs_cleanup' => strlen($product->name) > 60 || !empty($analysis['brand']),
    ];
}

// Filter to products that need cleanup
$needsCleanup = array_filter($cleanup, fn($p) => $p['needs_cleanup']);

echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║ PRODUCTS NEEDING NAME CLEANUP (" . count($needsCleanup) . ")                                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n\n";

foreach ($needsCleanup as $item) {
    echo "ID {$item['id']} | {$item['sku']}\n";
    echo "  ORIGINAL: {$item['original_name']}\n";
    echo "  BRAND: " . ($item['brand'] ?: 'N/A') . "\n";
    echo "  SIMPLIFIED: {$item['simplified_name']}\n";

    if (!empty($item['attributes'])) {
        echo "  ATTRIBUTES:\n";
        foreach ($item['attributes'] as $key => $value) {
            echo "    - {$key}: {$value}\n";
        }
    }
    echo "\n";
}

// Generate JSON output
$outputPath = __DIR__ . '/product-name-cleanup-mapping.json';
file_put_contents($outputPath, json_encode([
    'generated_at' => date('Y-m-d H:i:s'),
    'total_products' => count($products),
    'needs_cleanup' => count($needsCleanup),
    'products' => $cleanup,
], JSON_PRETTY_PRINT));

echo "\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║ SUMMARY                                                                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n\n";
echo "Total products: " . count($products) . "\n";
echo "Needs cleanup: " . count($needsCleanup) . "\n";
echo "Clean names: " . (count($products) - count($needsCleanup)) . "\n";
echo "\nMapping saved to: {$outputPath}\n\n";

/**
 * Analyze a product name to extract brand, simplified name, and attributes
 */
function analyzeProduct(string $name, string $sku): array
{
    $brand = extractBrand($name, $sku);
    $attributes = extractAllAttributes($name);
    $simplifiedName = simplifyName($name, $brand, $attributes);

    return [
        'brand' => $brand,
        'simplified_name' => $simplifiedName,
        'attributes' => $attributes,
    ];
}

/**
 * Extract brand from product name or SKU
 */
function extractBrand(string $name, string $sku): ?string
{
    // Known brands
    $brands = [
        'Serious Grit',
        'Amana Tool',
        'DEWALT',
        'Blum',
        'Titebond',
        'RELIABLE',
        'EPSON',
        'Regency',
        'HOZLY',
        'PAMAZY',
        'VBEST',
        'O\'SKOOL',
        'Labelife',
        'West Systems',
        'Jowatherm',
        'Rev-A-Shelf',
        'LeMans',
        'Kreg',
        'YUEERIO',
        'Felder',
    ];

    foreach ($brands as $brand) {
        if (stripos($name, $brand) !== false) {
            return $brand;
        }
    }

    // Check for Amazon SKU pattern
    if (strpos($sku, 'AMZ-') === 0) {
        // Try to extract brand from beginning of name
        $words = explode(' ', $name);
        if (count($words) > 0 && strlen($words[0]) > 2) {
            return $words[0];
        }
    }

    return null;
}

/**
 * Extract all attributes from product name
 */
function extractAllAttributes(string $name): array
{
    $attributes = [];

    // Size (e.g., "6-Inch", "12 inch", "3/8\"")
    if (preg_match('/(\d+(?:\/\d+)?)["\']?\s*(?:-)?(?:inch|in\b)/i', $name, $matches)) {
        $attributes['Size'] = $matches[1] . '"';
    }

    // Grit
    if (preg_match('/(\d+)\s*Grit/i', $name, $matches)) {
        $attributes['Grit'] = $matches[1];
    }

    // Length
    if (preg_match('/(\d+(?:\/\d+)?)["\']?\s*#?\d*\)?(?:\s+long)?(?=\s|$)/i', $name, $matches)) {
        if (!isset($attributes['Size'])) {
            $attributes['Length'] = $matches[1] . '"';
        }
    }

    // Gauge
    if (preg_match('/(\d+)\s*Gauge/i', $name, $matches)) {
        $attributes['Gauge'] = $matches[1];
    }

    // Pack size
    if (preg_match('/(\d+)[\s-]*(?:Pack|Pcs|Pieces)/i', $name, $matches)) {
        $attributes['Pack Size'] = $matches[1];
    }

    // Type indicators
    if (preg_match('/\((.*?(?:Compression|Down-?cut|Up-?cut|Spiral|Overlay|Thick|Thin).*?)\)/i', $name, $matches)) {
        $attributes['Type'] = trim($matches[1]);
    }

    // Overlay type
    if (preg_match('/(Full|Half|1\/2|Inset)\s+Overlay/i', $name, $matches)) {
        $attributes['Overlay Type'] = $matches[1] . ' Overlay';
    }

    return $attributes;
}

/**
 * Simplify product name by removing brand, marketing text, and extracted attributes
 */
function simplifyName(string $name, ?string $brand, array $attributes): string
{
    $simplified = $name;

    // Remove brand
    if ($brand) {
        $simplified = str_ireplace($brand, '', $simplified);
    }

    // Remove marketing phrases
    $marketingPhrases = [
        'Advanced Grain for Fast Cutting & Long Life',
        'Film-Backed Universal Fit',
        'Heavy Duty',
        'Ultra Durable',
        'Upgraded',
        'Premium',
        'Professional',
        'Industrial',
        'with Carabiner Hooks',
        'for Luggage Rack, Cargo, Hand Carts, Bike, Camping, etc',
        'Works with WorkForce Pro',
        'Compatible with Brother P Touch',
        'Designed in the USA',
        'Max Break Strength',
        'Heavy-Duty Stickyback Adhesive',
        'for Wood, Metal, Autobody, Paint, Air Sanders',
    ];

    foreach ($marketingPhrases as $phrase) {
        $simplified = str_ireplace($phrase, '', $simplified);
    }

    // Remove attribute values that are now in attributes array
    foreach ($attributes as $value) {
        // Be careful not to remove too much
        if (strlen($value) > 2) {
            $simplified = str_replace($value, '', $simplified);
        }
    }

    // Clean up extra spaces, dashes, and parentheses
    $simplified = preg_replace('/\s*-\s*-\s*/', ' - ', $simplified);
    $simplified = preg_replace('/\(\s*\)/', '', $simplified);
    $simplified = preg_replace('/\s+/', ' ', $simplified);
    $simplified = preg_replace('/\s*-\s*$/', '', $simplified);
    $simplified = trim($simplified, ' -,');

    // If name is still too long, try to extract core product type
    if (strlen($simplified) > 60) {
        $simplified = extractCoreProductType($name);
    }

    return $simplified;
}

/**
 * Extract core product type from overly long names
 */
function extractCoreProductType(string $name): string
{
    // Common product types
    $types = [
        'Sanding Discs',
        'Sandpaper',
        'Router Bit',
        'Bungee Cords',
        'Wood Glue',
        'Wood Screw',
        'Brad Nails',
        'Drawer Slides',
        'Hinges',
        'Edge Banding',
        'Saw Blades',
        'Dust Collection Bags',
        'Trash Pull Out',
        'Ink Cartridge',
    ];

    foreach ($types as $type) {
        if (stripos($name, $type) !== false) {
            // Try to keep size/dimension info
            $simplified = $type;

            if (preg_match('/(\d+(?:\/\d+)?)["\']?(?:-)?(?:inch|in)/i', $name, $matches)) {
                $simplified = $matches[1] . '" ' . $simplified;
            }

            return $simplified;
        }
    }

    // Fallback: use first few words
    $words = array_slice(explode(' ', $name), 0, 4);
    return implode(' ', $words);
}
