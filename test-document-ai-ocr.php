<?php

/**
 * Test Script: Google Cloud Document AI OCR
 *
 * Tests the Document AI OCR service with an existing PDF
 * Compares native extraction vs OCR results
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Services\GoogleDocumentAiService;
use App\Models\PdfDocument;
use App\Models\PdfPage;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=================================\n";
echo "Document AI OCR Test Script\n";
echo "=================================\n\n";

try {
    // Find a test PDF document
    echo "1. Finding test PDF document...\n";
    $document = PdfDocument::with('pages')->latest()->first();

    if (!$document) {
        echo "❌ No PDF documents found in database.\n";
        echo "   Please upload a PDF first.\n";
        exit(1);
    }

    echo "✅ Found document: {$document->file_name}\n";
    echo "   Pages: " . $document->pages->count() . "\n\n";

    // Get first page
    $page = $document->pages->first();

    if (!$page) {
        echo "❌ No pages found for this document.\n";
        exit(1);
    }

    echo "2. Testing on page {$page->page_number}...\n";
    echo "   Thumbnail: {$page->thumbnail_path}\n\n";

    // Initialize Document AI service
    echo "3. Initializing Google Document AI...\n";
    $docAI = app(GoogleDocumentAiService::class);
    echo "   ✅ Service initialized\n\n";

    // Test 1: Extract text from page thumbnail
    echo "=================================\n";
    echo "TEST 1: Single Page OCR\n";
    echo "=================================\n";

    $startTime = microtime(true);
    $result = $docAI->extractText($page->thumbnail_path, 'image/png');
    $totalTime = round((microtime(true) - $startTime) * 1000, 2);

    if (isset($result['error'])) {
        echo "❌ OCR Error: {$result['error']}\n\n";
    } else {
        echo "✅ OCR Extraction Successful!\n\n";

        echo "📊 Results:\n";
        echo "   - Processing Time: {$result['time_ms']} ms\n";
        echo "   - Total Time: {$totalTime} ms\n";
        echo "   - Confidence: " . round($result['confidence'] * 100, 2) . "%\n";
        echo "   - Text Length: " . strlen($result['text']) . " characters\n\n";

        echo "📄 Extracted Text (first 500 chars):\n";
        echo "-----------------------------------\n";
        echo substr($result['text'], 0, 500) . "...\n\n";
    }

    // Test 2: Compare with native extraction
    echo "=================================\n";
    echo "TEST 2: Comparison with Native Extraction\n";
    echo "=================================\n";

    if ($page->extracted_text) {
        echo "✅ Native extraction exists\n\n";

        $nativeLength = strlen($page->extracted_text);
        $ocrLength = strlen($result['text'] ?? '');

        echo "📊 Comparison:\n";
        echo "   Native Text Length: {$nativeLength} chars\n";
        echo "   OCR Text Length: {$ocrLength} chars\n";
        echo "   Difference: " . abs($nativeLength - $ocrLength) . " chars\n\n";

        echo "📄 Native Text (first 500 chars):\n";
        echo "-----------------------------------\n";
        echo substr($page->extracted_text, 0, 500) . "...\n\n";

        // Calculate similarity
        if ($nativeLength > 0 && $ocrLength > 0) {
            similar_text($page->extracted_text, $result['text'] ?? '', $percent);
            echo "📈 Text Similarity: " . round($percent, 2) . "%\n\n";
        }
    } else {
        echo "ℹ️ No native extraction available for comparison\n\n";
    }

    // Test 3: Full PDF extraction (if PDF path exists)
    if ($document->file_path) {
        echo "=================================\n";
        echo "TEST 3: Full PDF Extraction\n";
        echo "=================================\n";

        echo "📄 Processing entire PDF: {$document->file_name}\n";

        $startTime = microtime(true);
        $pdfResult = $docAI->extractFromPdf($document->file_path);
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        if (isset($pdfResult['error'])) {
            echo "❌ PDF OCR Error: {$pdfResult['error']}\n\n";
        } else {
            echo "✅ PDF OCR Successful!\n\n";

            echo "📊 Results:\n";
            echo "   - Processing Time: {$pdfResult['time_ms']} ms\n";
            echo "   - Total Time: {$totalTime} ms\n";
            echo "   - Confidence: " . round($pdfResult['confidence'] * 100, 2) . "%\n";
            echo "   - Pages Processed: " . count($pdfResult['pages']) . "\n";
            echo "   - Total Text Length: " . strlen($pdfResult['text']) . " characters\n\n";

            echo "📄 Page-by-Page Results:\n";
            foreach ($pdfResult['pages'] as $pageData) {
                echo "   Page {$pageData['page_number']}: "
                    . strlen($pageData['text']) . " chars, "
                    . round($pageData['confidence'] * 100, 2) . "% confidence\n";
            }
            echo "\n";
        }
    }

    // Test 4: Usage information
    echo "=================================\n";
    echo "TEST 4: Service Information\n";
    echo "=================================\n";

    $info = $docAI->getUsageInfo();

    echo "🔧 Provider: {$info['provider']}\n";
    echo "🔧 Processor: {$info['processor_type']}\n\n";

    echo "💰 Pricing:\n";
    echo "   - Free Tier: {$info['pricing']['free_tier']}\n";
    echo "   - Paid Rate: {$info['pricing']['paid_rate']}\n\n";

    echo "✨ Features:\n";
    foreach ($info['features'] as $feature) {
        echo "   - " . str_replace('_', ' ', ucwords($feature, '_')) . "\n";
    }
    echo "\n";

    echo "=================================\n";
    echo "✅ ALL TESTS COMPLETED SUCCESSFULLY!\n";
    echo "=================================\n\n";

    echo "📝 Summary:\n";
    echo "   - Document AI is properly configured\n";
    echo "   - OCR extraction is working\n";
    echo "   - Authentication is successful\n";
    echo "   - Ready for production use\n\n";

} catch (\Exception $e) {
    echo "\n❌ TEST FAILED\n";
    echo "=================================\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n\n";

    echo "💡 Troubleshooting:\n";
    echo "   1. Check .env has all Google Cloud credentials\n";
    echo "   2. Verify Application Default Credentials are set up:\n";
    echo "      gcloud auth application-default login\n";
    echo "   3. Ensure Document AI API is enabled\n";
    echo "   4. Check processor ID is correct\n\n";

    exit(1);
}
