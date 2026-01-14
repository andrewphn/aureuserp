<?php

/**
 * Test script for AI Document Scanning
 * 
 * Run with: php artisan tinker < scripts/test-document-scan.php
 * Or: php scripts/test-document-scan.php (requires bootstrap)
 */

// Bootstrap Laravel if running directly
if (!defined('LARAVEL_START')) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

use App\Services\AI\DocumentScannerService;
use App\Services\AI\GeminiService;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "==========================================================\n";
echo "   AI DOCUMENT SCANNER TEST\n";
echo "==========================================================\n\n";

// The test document
$documentPath = base_path('sample/Inventory/Scans/Image_20260114_0001.pdf');

if (!file_exists($documentPath)) {
    echo "❌ ERROR: Document not found at: {$documentPath}\n";
    exit(1);
}

echo "📄 Document: " . basename($documentPath) . "\n";
echo "   Size: " . number_format(filesize($documentPath) / 1024, 2) . " KB\n";
echo "   Type: " . mime_content_type($documentPath) . "\n\n";

// Step 1: Initialize Services
echo "STEP 1: Initialize Services\n";
echo str_repeat("-", 50) . "\n";

try {
    $geminiService = app(GeminiService::class);
    echo "✅ GeminiService initialized\n";
    
    $scannerService = app(DocumentScannerService::class);
    echo "✅ DocumentScannerService initialized\n";
    echo "   - Confidence Threshold: " . ($scannerService->getConfidenceThreshold() * 100) . "%\n";
    echo "   - Auto-Apply Threshold: " . ($scannerService->getAutoApplyThreshold() * 100) . "%\n";
} catch (\Exception $e) {
    echo "❌ ERROR initializing services: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Step 2: Read the document
echo "STEP 2: Read Document\n";
echo str_repeat("-", 50) . "\n";

echo "✅ Document ready for scanning\n";
echo "   - Path: " . $documentPath . "\n";

echo "\n";

// Step 3: Scan as packing slip (for receiving)
echo "STEP 3: Scan Document (as Packing Slip)\n";
echo str_repeat("-", 50) . "\n";
echo "⏳ Sending to Gemini AI for analysis...\n\n";

$startTime = microtime(true);

try {
    // Use file path directly - GeminiService will handle the conversion
    $result = $scannerService->scanDocument(
        $documentPath,
        DocumentScannerService::TYPE_PACKING_SLIP
    );
    
    $elapsed = round((microtime(true) - $startTime) * 1000);
    
    echo "✅ Scan completed in {$elapsed}ms\n\n";
    
    // Step 4: Display Results
    echo "STEP 4: Results\n";
    echo str_repeat("-", 50) . "\n";
    
    if ($result['success']) {
        $data = $result['data'];
        
        echo "📊 EXTRACTION RESULTS:\n\n";
        
        // Overall confidence
        $confidence = $data['confidence'] ?? 0;
        $confidencePercent = round($confidence * 100);
        $confidenceColor = $confidence >= 0.8 ? '🟢' : ($confidence >= 0.5 ? '🟡' : '🔴');
        echo "   Overall Confidence: {$confidenceColor} {$confidencePercent}%\n";
        echo "   Needs Review: " . (($data['needs_review'] ?? true) ? '⚠️ Yes' : '✅ No') . "\n";
        echo "   Can Auto-Apply: " . (($data['can_auto_apply'] ?? false) ? '✅ Yes' : '❌ No') . "\n\n";
        
        // Vendor info
        echo "📦 VENDOR:\n";
        if (isset($data['vendor'])) {
            echo "   Name: " . ($data['vendor']['name'] ?? 'N/A') . "\n";
            echo "   Address: " . ($data['vendor']['address'] ?? 'N/A') . "\n";
        }
        if (isset($data['vendor_match'])) {
            echo "   Match Status: " . ($data['vendor_match']['matched'] ? '✅ MATCHED' : '❌ NOT FOUND') . "\n";
            if ($data['vendor_match']['matched']) {
                echo "   Matched ID: " . $data['vendor_match']['id'] . "\n";
                echo "   Matched Name: " . $data['vendor_match']['name'] . "\n";
                echo "   Match Confidence: " . round(($data['vendor_match']['confidence'] ?? 0) * 100) . "%\n";
                echo "   Match Method: " . ($data['vendor_match']['match_method'] ?? 'N/A') . "\n";
            }
        }
        echo "\n";
        
        // Document info
        echo "📋 DOCUMENT INFO:\n";
        if (isset($data['document'])) {
            echo "   Slip Number: " . ($data['document']['slip_number'] ?? 'N/A') . "\n";
            echo "   Ship Date: " . ($data['document']['ship_date'] ?? 'N/A') . "\n";
            echo "   PO Reference: " . ($data['document']['po_reference'] ?? 'N/A') . "\n";
            echo "   Tracking Number: " . ($data['document']['tracking_number'] ?? 'N/A') . "\n";
        }
        echo "\n";
        
        // PO Match
        echo "🛒 PURCHASE ORDER MATCH:\n";
        if (isset($data['po_match'])) {
            echo "   Match Status: " . ($data['po_match']['matched'] ? '✅ MATCHED' : '❌ NOT FOUND') . "\n";
            if ($data['po_match']['matched']) {
                echo "   PO ID: " . $data['po_match']['id'] . "\n";
                echo "   PO Name: " . $data['po_match']['name'] . "\n";
                echo "   PO State: " . ($data['po_match']['state'] ?? 'N/A') . "\n";
                echo "   Lines Count: " . ($data['po_match']['lines_count'] ?? 0) . "\n";
            }
        }
        echo "\n";
        
        // Line Items
        echo "📝 LINE ITEMS:\n";
        $lineItems = $data['line_items'] ?? [];
        if (count($lineItems) > 0) {
            echo "   Total Lines: " . count($lineItems) . "\n";
            
            $matched = 0;
            $unmatched = 0;
            
            foreach ($lineItems as $i => $line) {
                $num = $i + 1;
                echo "\n   Line #{$num}:\n";
                echo "      SKU: " . ($line['sku'] ?? 'N/A') . "\n";
                echo "      Description: " . ($line['description'] ?? 'N/A') . "\n";
                echo "      Qty Shipped: " . ($line['quantity_shipped'] ?? $line['quantity'] ?? 0) . "\n";
                echo "      Unit: " . ($line['unit'] ?? 'N/A') . "\n";
                
                if (isset($line['product_match'])) {
                    $pm = $line['product_match'];
                    if ($pm['matched']) {
                        $matched++;
                        echo "      Product Match: ✅ MATCHED\n";
                        echo "         Product ID: " . ($pm['product_id'] ?? 'N/A') . "\n";
                        echo "         Product Name: " . ($pm['product_name'] ?? 'N/A') . "\n";
                        echo "         Confidence: " . round(($pm['confidence'] ?? 0) * 100) . "%\n";
                        echo "         Match Method: " . ($pm['match_method'] ?? 'N/A') . "\n";
                    } else {
                        $unmatched++;
                        echo "      Product Match: ❌ NOT FOUND\n";
                        echo "         Suggestion: " . ($pm['suggestion'] ?? 'N/A') . "\n";
                    }
                }
                
                if (isset($line['requires_review']) && $line['requires_review']) {
                    echo "      ⚠️ REQUIRES MANUAL REVIEW\n";
                }
            }
            
            echo "\n   📊 Summary:\n";
            echo "      Matched Products: {$matched}\n";
            echo "      Unmatched Products: {$unmatched}\n";
        } else {
            echo "   No line items extracted\n";
        }
        
        // Package info
        if (isset($data['package_info'])) {
            echo "\n📦 PACKAGE INFO:\n";
            echo "   Boxes: " . ($data['package_info']['boxes'] ?? 'N/A') . "\n";
            echo "   Weight: " . ($data['package_info']['weight'] ?? 'N/A') . "\n";
            echo "   Carrier: " . ($data['package_info']['carrier'] ?? 'N/A') . "\n";
        }
        
        // Stats
        if (isset($data['stats'])) {
            echo "\n📈 STATISTICS:\n";
            echo "   Lines Total: " . ($data['stats']['lines_total'] ?? 0) . "\n";
            echo "   Lines Matched: " . ($data['stats']['lines_matched'] ?? 0) . "\n";
            echo "   Lines Unmatched: " . ($data['stats']['lines_unmatched'] ?? 0) . "\n";
            echo "   Match Rate: " . ($data['stats']['match_rate'] ?? 0) . "%\n";
        }
        
        // Scan Log
        if (isset($result['scan_log_id'])) {
            echo "\n📝 AUDIT LOG:\n";
            echo "   Scan Log ID: " . $result['scan_log_id'] . "\n";
        }
        
    } else {
        echo "❌ Scan failed!\n";
        echo "   Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR during scan: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
echo "==========================================================\n";
echo "   TEST COMPLETE\n";
echo "==========================================================\n\n";
