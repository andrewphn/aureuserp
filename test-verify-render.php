#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VERIFYING ORDER 300 TEMPLATE RENDERING ===\n\n";

$order = \Webkul\Sale\Models\Order::with(['partner', 'project', 'lines', 'lines.product'])->find(300);
$template = \Webkul\Sale\Models\DocumentTemplate::find(6);

if (!$order || !$template) {
    echo "❌ Order 300 or Template 6 not found\n";
    exit(1);
}

// Show current database values
$line = $order->lines->first();
echo "Current Database Values:\n";
echo "  Order Total: $" . number_format($order->amount_total, 2) . "\n";
echo "  Line Count: {$order->lines->count()}\n";
echo "  Quantity: " . number_format($line->product_uom_qty, 0) . "\n";
echo "  Rate: $" . number_format($line->price_unit, 2) . "\n";
echo "  Amount: $" . number_format($line->price_total, 2) . "\n\n";

// Render the template
$renderer = new \Webkul\Sale\Services\TemplateRenderer();
$html = $renderer->render($template, $order);

// Check if rendered values match database
echo "Checking Rendered HTML:\n";

$qty = number_format($line->product_uom_qty, 0);
$rate = number_format($line->price_unit, 2);
$amount = number_format($line->price_total, 2);

$qtyFound = preg_match('/>\s*' . preg_quote($qty) . '\s*</', $html);
$rateFound = strpos($html, $rate) !== false || strpos($html, '$' . $rate) !== false;
$amountFound = strpos($html, str_replace(',', ',', $amount)) !== false || strpos($html, '$' . str_replace(',', ',', $amount)) !== false;

echo "  Quantity ({$qty}): " . ($qtyFound ? '✅ FOUND' : '❌ NOT FOUND') . "\n";
echo "  Rate (\${$rate}): " . ($rateFound ? '✅ FOUND' : '❌ NOT FOUND') . "\n";
echo "  Amount (\${$amount}): " . ($amountFound ? '✅ FOUND' : '❌ NOT FOUND') . "\n\n";

// Try to extract the actual line item row from HTML
if (preg_match('/<tr[^>]*>.*?<td[^>]*>.*?(Cabinet|Product).*?<\/td>.*?<td[^>]*>.*?(\d+).*?<\/td>.*?<td[^>]*>\$?([0-9,]+\.\d{2}).*?<\/td>.*?<td[^>]*>\$?([0-9,]+\.\d{2}).*?<\/td>.*?<\/tr>/s', $html, $matches)) {
    echo "Extracted Line Item Row:\n";
    echo "  Product: {$matches[1]}\n";
    echo "  Quantity: {$matches[2]}\n";
    echo "  Rate: \${$matches[3]}\n";
    echo "  Amount: \${$matches[4]}\n\n";

    // Validate against database
    $extractedQty = (int)$matches[2];
    $extractedRate = str_replace(',', '', $matches[3]);
    $extractedAmount = str_replace(',', '', $matches[4]);

    $qtyMatch = $extractedQty == $line->product_uom_qty;
    $rateMatch = abs($extractedRate - $line->price_unit) < 0.01;
    $amountMatch = abs($extractedAmount - $line->price_total) < 0.01;

    echo "Validation:\n";
    echo "  Quantity Match: " . ($qtyMatch ? '✅ YES' : '❌ NO') . "\n";
    echo "  Rate Match: " . ($rateMatch ? '✅ YES' : '❌ NO') . "\n";
    echo "  Amount Match: " . ($amountMatch ? '✅ YES' : '❌ NO') . "\n\n";

    if ($qtyMatch && $rateMatch && $amountMatch) {
        echo "🎉 SUCCESS! All values match database correctly.\n";
    } else {
        echo "⚠️  WARNING: Some values don't match!\n";
    }
} else {
    echo "⚠️  Could not extract line item row from HTML.\n";
    echo "Searching for any occurrence of expected values...\n\n";

    // Show a snippet of HTML around where values should be
    if (preg_match('/<table[^>]*>.*?<\/table>/s', $html, $tableMatch)) {
        $tableHtml = $tableMatch[0];
        echo "Table HTML snippet (first 1000 chars):\n";
        echo substr($tableHtml, 0, 1000) . "\n...\n";
    }
}

echo "\n✅ Verification complete!\n";
