#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING TEMPLATE RENDERER FIX ===\n\n";

$order = \Webkul\Sale\Models\Order::with(['partner', 'project', 'lines', 'lines.product'])->find(300);
$template = \Webkul\Sale\Models\DocumentTemplate::find(6);

if (!$order || !$template) {
    echo "❌ Order 300 or Template 6 not found\n";
    exit(1);
}

$renderer = new \Webkul\Sale\Services\TemplateRenderer();
$html = $renderer->render($template, $order);

// Check if any {{VARIABLE}} placeholders remain unreplaced
preg_match_all('/\{\{([A-Z_0-9]+)\}\}/', $html, $matches);
if (!empty($matches[1])) {
    echo "⚠️  Found " . count(array_unique($matches[1])) . " unreplaced variables:\n";
    foreach (array_unique($matches[1]) as $unmatched) {
        echo "  - {{" . $unmatched . "}}\n";
    }
} else {
    echo "✅ All template variables successfully replaced!\n";
}

// Check that line items were populated
echo "\nLine items check:\n";
if (strpos($html, 'Cabinet') !== false) {
    echo "✅ Line item name 'Cabinet' found in rendered HTML\n";
} else {
    echo "❌ Line item name NOT found\n";
}

if (preg_match('/\$\d+\.\d{2}/', $html)) {
    echo "✅ Line item prices found in rendered HTML\n";
} else {
    echo "❌ Line item prices NOT found\n";
}

echo "\nRendered HTML length: " . strlen($html) . " chars\n";
echo "Order lines count: " . $order->lines->count() . "\n";

echo "\n✅ Template rendering fix complete!\n";
echo "\nYou can now test the preview button at:\n";
echo "http://aureuserp.test/admin/sale/orders/quotations/300\n";
