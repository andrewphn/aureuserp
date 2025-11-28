#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Clear ALL caches
echo "Clearing all caches...\n";
\Artisan::call('cache:clear');
\Artisan::call('config:clear');
\Artisan::call('view:clear');

echo "\n=== FRESH RENDER TEST ===\n\n";

$order = \Webkul\Sale\Models\Order::with(['partner', 'project', 'lines', 'lines.product'])->find(300);
$template = \Webkul\Sale\Models\DocumentTemplate::find(6);

echo "Order ID: {$order->id}\n";
echo "Template ID: {$template->id}\n\n";

$renderer = new \Webkul\Sale\Services\TemplateRenderer();
$html = $renderer->render($template, $order);

// Find line item table rows
echo "Searching for line item data in rendered HTML...\n\n";

// Look for the specific values
$found = [];
$found['qty_2'] = strpos($html, '>2<') !== false;
$found['rate_276'] = strpos($html, '276.00') !== false;
$found['amount_634'] = strpos($html, '634.80') !== false;

echo "Values found in HTML:\n";
echo "  Quantity (2): " . ($found['qty_2'] ? '✅ FOUND' : '❌ NOT FOUND') . "\n";
echo "  Rate (276.00): " . ($found['rate_276'] ? '✅ FOUND' : '❌ NOT FOUND') . "\n";
echo "  Amount (634.80): " . ($found['amount_634'] ? '✅ FOUND' : '❌ NOT FOUND') . "\n\n";

// Extract actual line item row
if (preg_match('/<tr[^>]*>.*?<td[^>]*>.*?Cabinet.*?<\/td>.*?<td[^>]*>.*?(\d+).*?<\/td>.*?<td[^>]*>.*?([\d.]+).*?<\/td>.*?<td[^>]*>.*?([\d.]+).*?<\/td>.*?<\/tr>/s', $html, $matches)) {
    echo "Extracted line item row:\n";
    echo "  Quantity: {$matches[1]}\n";
    echo "  Rate: {$matches[2]}\n";
    echo "  Amount: {$matches[3]}\n";
}

echo "\n✅ Test complete!\n";
