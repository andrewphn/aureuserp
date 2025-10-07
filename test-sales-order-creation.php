#!/usr/bin/env php
<?php

/**
 * Test Sales Order Creation from PDF
 *
 * This script tests the complete workflow:
 * 1. Parse PDF
 * 2. Create sales order
 * 3. Display sales order details
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PdfParsingService;
use App\Models\PdfDocument;
use Illuminate\Support\Facades\DB;

echo "Sales Order Creation Test\n";
echo "==========================\n\n";

// Get or create test project with customer
$project = DB::table('projects_projects')->first();
if (!$project) {
    echo "❌ No projects found in database\n";
    exit(1);
}

echo "Using Project:\n";
echo "  ID: {$project->id}\n";
echo "  Name: {$project->name}\n";

// Make sure project has a partner
if (!$project->partner_id) {
    // Get first partner
    $partner = DB::table('partners_partners')->first();
    if (!$partner) {
        echo "❌ No partners found in database\n";
        exit(1);
    }

    DB::table('projects_projects')
        ->where('id', $project->id)
        ->update(['partner_id' => $partner->id]);

    $project->partner_id = $partner->id;
    echo "  ✅ Assigned Partner: {$partner->name}\n";
} else {
    $partner = DB::table('partners_partners')->where('id', $project->partner_id)->first();
    echo "  Partner: {$partner->name}\n";
}

echo "\n";

// Get the test PDF document
$pdfDocument = DB::table('pdf_documents')
    ->where('file_path', 'pdf-documents/test-friendship-revision.pdf')
    ->first();

if (!$pdfDocument) {
    echo "❌ Test PDF not found. Run test-pdf-parsing.php first.\n";
    exit(1);
}

$pdfDoc = PdfDocument::find($pdfDocument->id);

echo "Using PDF Document:\n";
echo "  ID: {$pdfDocument->id}\n";
echo "  File: {$pdfDocument->file_name}\n\n";

// Parse PDF
$parsingService = new PdfParsingService();

echo "Step 1: Parsing PDF...\n";
echo "=======================\n";

try {
    $parsedData = $parsingService->parseArchitecturalDrawing($pdfDoc);

    echo "✅ Extracted " . count($parsedData['line_items']) . " line items\n\n";

    foreach ($parsedData['line_items'] as $index => $item) {
        echo "  " . ($index + 1) . ". {$item['product_name']} - {$item['quantity']} {$item['unit']} @ $" . number_format($item['unit_price'], 2) . "/{$item['unit']}\n";
    }

} catch (\Exception $e) {
    echo "❌ Error parsing PDF: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nStep 2: Creating Sales Order...\n";
echo "================================\n";

try {
    $salesOrderId = $parsingService->createSalesOrderFromParsedData(
        $parsedData,
        $project->id,
        $project->partner_id
    );

    echo "✅ Created Sales Order ID: {$salesOrderId}\n\n";

    // Retrieve and display sales order
    $salesOrder = DB::table('sales_orders')->where('id', $salesOrderId)->first();

    echo "Sales Order Details:\n";
    echo "====================\n";
    echo "  ID: {$salesOrder->id}\n";
    echo "  Name: {$salesOrder->name}\n";
    echo "  Project: {$project->name}\n";
    echo "  Customer: {$partner->name}\n";
    echo "  State: {$salesOrder->state}\n";
    echo "  Invoice Status: {$salesOrder->invoice_status}\n";
    echo "  Date: {$salesOrder->date_order}\n";
    echo "\n";

    // Get sales order lines
    $lines = DB::table('sales_order_lines')
        ->where('order_id', $salesOrderId)
        ->get();

    echo "Line Items:\n";
    echo "===========\n";

    $total = 0;
    foreach ($lines as $index => $line) {
        $lineTotal = $line->price_subtotal;
        $total += $lineTotal;

        echo "\n  Line " . ($index + 1) . ":\n";
        echo "    Product: {$line->name}\n";
        echo "    Quantity: {$line->product_uom_qty}\n";
        echo "    Unit Price: $" . number_format($line->price_unit, 2) . "\n";
        echo "    Subtotal: $" . number_format($lineTotal, 2) . "\n";
    }

    echo "\n" . str_repeat('=', 50) . "\n";
    echo "  Total: $" . number_format($total, 2) . "\n";
    echo str_repeat('=', 50) . "\n";

    echo "\n✅ Sales order created successfully!\n";

    // Verify sales order name follows project numbering
    if ($salesOrder->name && str_contains($salesOrder->name, $project->project_number ?? '')) {
        echo "✅ Sales order number follows project numbering: {$salesOrder->name}\n";
    }

} catch (\Exception $e) {
    echo "❌ Error creating sales order: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n\n✅ Test completed successfully!\n";
echo "\nThe PDF-to-Sales-Order workflow is now functional.\n";
echo "Users can upload architectural PDFs to projects and automatically create sales orders.\n";
