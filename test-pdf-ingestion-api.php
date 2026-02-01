<?php

/**
 * Test script for PDF Ingestion API
 *
 * Usage:
 *   php test-pdf-ingestion-api.php <pdf_document_id>
 *
 * Make sure you have:
 * 1. A valid PDF document in the database
 * 2. GEMINI_API_KEY or GOOGLE_API_KEY set in .env
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PdfDocument;
use App\Services\AiPdfParsingService;
use Illuminate\Support\Facades\Log;

echo "=== PDF Ingestion API Test ===\n\n";

// Get PDF document ID from command line or use a default
$pdfDocumentId = $argv[1] ?? null;

if (!$pdfDocumentId) {
    // Find the most recent PDF document
    $pdfDocument = PdfDocument::latest()->first();
    if (!$pdfDocument) {
        echo "Error: No PDF documents found in database.\n";
        echo "Usage: php test-pdf-ingestion-api.php <pdf_document_id>\n";
        exit(1);
    }
    $pdfDocumentId = $pdfDocument->id;
    echo "Using most recent PDF document: ID {$pdfDocumentId}\n";
} else {
    $pdfDocument = PdfDocument::find($pdfDocumentId);
    if (!$pdfDocument) {
        echo "Error: PDF document with ID {$pdfDocumentId} not found.\n";
        exit(1);
    }
}

echo "PDF Document: {$pdfDocument->file_name}\n";
echo "File Path: {$pdfDocument->file_path}\n";
echo "Page Count: {$pdfDocument->page_count}\n";
echo "Current Status: {$pdfDocument->processing_status}\n";

// Check if API keys are configured
$geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
if (empty($geminiKey)) {
    echo "\nWarning: No Gemini API key configured. Set GOOGLE_API_KEY or GEMINI_API_KEY in .env\n";
    exit(1);
}
echo "\nGemini API Key: " . substr($geminiKey, 0, 10) . "...\n";

echo "\n--- Testing AI PDF Parsing Service ---\n";

try {
    $aiService = app(AiPdfParsingService::class);

    echo "Classifying document pages...\n";
    $startTime = microtime(true);

    $classifications = $aiService->classifyDocumentPages($pdfDocument);

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "Classification completed in {$elapsed}s\n\n";

    if (isset($classifications['error'])) {
        echo "Error: {$classifications['error']}\n";
        exit(1);
    }

    echo "Page Classifications:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-6s %-15s %-30s %s\n", "Page", "Purpose", "Label", "Confidence");
    echo str_repeat('-', 80) . "\n";

    foreach ($classifications as $page) {
        printf(
            "%-6s %-15s %-30s %.0f%%\n",
            $page['page_number'] ?? '?',
            $page['primary_purpose'] ?? 'unknown',
            substr($page['page_label'] ?? '', 0, 30),
            ($page['confidence'] ?? 0) * 100
        );
    }

    echo str_repeat('-', 80) . "\n";

    // Test cover page parsing if there is one
    $coverPage = collect($classifications)->firstWhere('primary_purpose', 'cover');
    if ($coverPage) {
        $pageNumber = $coverPage['page_number'];
        $page = $pdfDocument->pages()->where('page_number', $pageNumber)->first();

        if ($page) {
            echo "\n--- Testing Cover Page Parsing (Page {$pageNumber}) ---\n";
            $coverData = $aiService->parseCoverPage($page);

            echo "Extracted Cover Page Data:\n";
            print_r($coverData);
        }
    }

    // Test elevation parsing if there is one
    $elevationPage = collect($classifications)->firstWhere('primary_purpose', 'elevations');
    if ($elevationPage) {
        $pageNumber = $elevationPage['page_number'];
        $page = $pdfDocument->pages()->where('page_number', $pageNumber)->first();

        if ($page) {
            echo "\n--- Testing Elevation Parsing (Page {$pageNumber}) ---\n";
            $elevationData = $aiService->parseElevation($page);

            echo "Extracted Elevation Data:\n";
            print_r($elevationData);
        }
    }

    echo "\n=== Test Complete ===\n";
    echo "The AI PDF parsing service is working correctly.\n";
    echo "You can now test the full API endpoint or set up the n8n workflow.\n";

} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
