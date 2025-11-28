#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING LINE ITEM VALUE RENDERING ===\n\n";

$order = \Webkul\Sale\Models\Order::with(['partner', 'project', 'lines', 'lines.product'])->find(300);
$template = \Webkul\Sale\Models\DocumentTemplate::find(6);

if (!$order || !$template) {
    echo "❌ Order 300 or Template 6 not found\n";
    exit(1);
}

$renderer = new \Webkul\Sale\Services\TemplateRenderer();
$html = $renderer->render($template, $order);

// Check the actual line item data
$line = $order->lines->first();
echo "Line Item Data from Database:\n";
echo "  Quantity (product_uom_qty): " . $line->product_uom_qty . "\n";
echo "  Rate (price_unit): $" . number_format($line->price_unit, 2) . "\n";
echo "  Amount (price_total): $" . number_format($line->price_total, 2) . "\n\n";

// Check if these values appear in the rendered HTML
echo "Checking Rendered HTML:\n";

// Check for quantity
if (preg_match('/>\s*' . number_format($line->product_uom_qty, 0) . '\s*</', $html)) {
    echo "  ✅ Quantity " . number_format($line->product_uom_qty, 0) . " found\n";
} else {
    echo "  ❌ Quantity " . number_format($line->product_uom_qty, 0) . " NOT found\n";
}

// Check for rate
if (strpos($html, number_format($line->price_unit, 2)) !== false) {
    echo "  ✅ Rate $" . number_format($line->price_unit, 2) . " found\n";
} else {
    echo "  ❌ Rate $" . number_format($line->price_unit, 2) . " NOT found\n";
}

// Check for amount
if (strpos($html, number_format($line->price_total, 2)) !== false) {
    echo "  ✅ Amount $" . number_format($line->price_total, 2) . " found\n";
} else {
    echo "  ❌ Amount $" . number_format($line->price_total, 2) . " NOT found\n";
}

echo "\n✅ Test complete!\n";
echo "You can verify these values at: http://aureuserp.test/admin/sale/orders/quotations/300\n";
