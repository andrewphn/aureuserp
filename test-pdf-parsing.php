#!/usr/bin/env php
<?php

/**
 * Test PDF Parsing Service
 *
 * This script tests the PDF parsing functionality by:
 * 1. Creating a test PdfDocument record
 * 2. Parsing the sample architectural drawing
 * 3. Displaying extracted line items
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PdfParsingService;
use App\Models\PdfDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "PDF Parsing Service Test\n";
echo "========================\n\n";

// Copy the sample PDF to the storage directory
$sourcePdf = '/Users/andrewphan/tcsadmin/aureuserp/docs/sample/9.28.25_25FriendshipRevision4.pdf';
$storagePath = 'pdf-documents/test-friendship-revision.pdf';

if (!file_exists($sourcePdf)) {
    echo "❌ Source PDF not found: {$sourcePdf}\n";
    exit(1);
}

// Copy to storage
$publicPath = storage_path('app/public/pdf-documents');
if (!is_dir($publicPath)) {
    mkdir($publicPath, 0755, true);
}

$destPath = storage_path('app/public/' . $storagePath);
copy($sourcePdf, $destPath);
echo "✅ Copied PDF to storage: {$storagePath}\n\n";

// Get file info
$fileSize = filesize($destPath);
$fileSizeMB = round($fileSize / 1024 / 1024, 2);

// Create or get test PdfDocument record
$pdfDocument = DB::table('pdf_documents')
    ->where('file_path', $storagePath)
    ->first();

if (!$pdfDocument) {
    $pdfDocId = DB::table('pdf_documents')->insertGetId([
        'module_type' => 'Webkul\Project\Models\Project',
        'module_id' => 1, // Assuming project ID 1 exists
        'file_name' => '9.28.25_25FriendshipRevision4.pdf',
        'file_path' => $storagePath,
        'file_size' => $fileSize,
        'mime_type' => 'application/pdf',
        'document_type' => 'drawing',
        'uploaded_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $pdfDocument = (object)[
        'id' => $pdfDocId,
        'file_path' => $storagePath,
        'file_name' => '9.28.25_25FriendshipRevision4.pdf',
        'file_size' => $fileSize,
    ];

    echo "✅ Created PdfDocument record (ID: {$pdfDocument->id})\n";
} else {
    echo "✅ Using existing PdfDocument record (ID: {$pdfDocument->id})\n";
}

echo "   File: {$pdfDocument->file_name}\n";
echo "   Size: {$fileSizeMB} MB\n\n";

// Parse the PDF
echo "Parsing PDF...\n";
echo "==============\n";

$parsingService = new PdfParsingService();

try {
    $pdfDoc = PdfDocument::find($pdfDocument->id);
    $parsedData = $parsingService->parseArchitecturalDrawing($pdfDoc);

    echo "\n✅ Successfully parsed PDF\n\n";

    echo "Extracted Line Items:\n";
    echo "====================\n";

    if (empty($parsedData['line_items'])) {
        echo "❌ No line items found\n";
    } else {
        $totalMatched = 0;
        $totalUnmatched = 0;

        foreach ($parsedData['line_items'] as $index => $item) {
            $status = $item['product_id'] ? '✅' : '❌';
            $matched = $item['product_id'] ? 'MATCHED' : 'UNMATCHED';

            if ($item['product_id']) {
                $totalMatched++;
            } else {
                $totalUnmatched++;
            }

            echo "\n{$status} Line Item " . ($index + 1) . " [{$matched}]:\n";
            echo "   Raw Name: {$item['raw_name']}\n";
            echo "   Product: {$item['product_name']}\n";
            echo "   Quantity: {$item['quantity']} {$item['unit']}\n";

            if ($item['product_id']) {
                echo "   Unit Price: $" . number_format($item['unit_price'], 2) . "/{$item['unit']}\n";
                echo "   Line Total: $" . number_format($item['quantity'] * $item['unit_price'], 2) . "\n";

                if (!empty($item['attribute_selections'])) {
                    echo "   Attributes:\n";
                    foreach ($item['attribute_selections'] as $attr) {
                        echo "      - {$attr['attribute_name']}: {$attr['option_name']}\n";
                    }
                }
            }
        }

        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Summary:\n";
        echo "  Total Items: " . count($parsedData['line_items']) . "\n";
        echo "  ✅ Matched: {$totalMatched}\n";
        echo "  ❌ Unmatched: {$totalUnmatched}\n";

        // Calculate total if all matched
        if ($totalUnmatched === 0) {
            $total = array_sum(array_map(fn($item) => $item['quantity'] * $item['unit_price'], $parsedData['line_items']));
            echo "\n  💰 Estimated Total: $" . number_format($total, 2) . "\n";
        }
    }

    // Show raw text excerpt
    echo "\n\nRaw PDF Text (first 500 chars):\n";
    echo "===============================\n";
    echo substr($parsedData['raw_text'], 0, 500) . "...\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n\n✅ Test completed successfully!\n";
echo "\nNext steps:\n";
echo "1. Upload a PDF to a project in the UI\n";
echo "2. Click 'Create Sales Order' button on the PDF\n";
echo "3. Review the created sales order\n";
