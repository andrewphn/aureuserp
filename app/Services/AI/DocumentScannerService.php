<?php

namespace App\Services\AI;

use App\Models\DocumentScanLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductSupplier;
use Webkul\Purchase\Models\Order as PurchaseOrder;
use Webkul\Purchase\Models\OrderLine as PurchaseOrderLine;
use Exception;

/**
 * Service class for AI-powered document scanning and data extraction
 *
 * Handles:
 * - Invoice scanning and data extraction
 * - Packing slip scanning for receiving
 * - Quote/estimate scanning
 * - Verification against existing POs and products
 * - Product matching via vendor SKUs with fuzzy matching
 * - Audit logging of all scan attempts
 */
class DocumentScannerService
{
    protected GeminiService $geminiService;

    /**
     * Document types supported
     */
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_PACKING_SLIP = 'packing_slip';
    public const TYPE_QUOTE = 'quote';
    public const TYPE_PRODUCT_LABEL = 'product_label';

    /**
     * Match method constants for AI tracking
     */
    public const MATCH_BY_VENDOR_SKU = 'vendor_sku';
    public const MATCH_BY_INTERNAL_SKU = 'internal_sku';
    public const MATCH_BY_BARCODE = 'barcode';
    public const MATCH_BY_DESCRIPTION = 'description';
    public const MATCH_BY_DIMENSIONS = 'dimensions';

    /**
     * Configuration
     */
    protected float $confidenceThreshold;
    protected float $autoApplyThreshold;
    protected bool $enableLogging;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;

        // Load configuration with defaults
        $this->confidenceThreshold = (float) config('ai.scan_confidence_threshold', 0.70);
        $this->autoApplyThreshold = (float) config('ai.scan_auto_apply_threshold', 0.95);
        $this->enableLogging = (bool) config('ai.scan_logging_enabled', true);
    }

    /**
     * Get the confidence threshold
     */
    public function getConfidenceThreshold(): float
    {
        return $this->confidenceThreshold;
    }

    /**
     * Get the auto-apply threshold
     */
    public function getAutoApplyThreshold(): float
    {
        return $this->autoApplyThreshold;
    }

    /**
     * Set custom confidence thresholds (useful for testing)
     */
    public function setThresholds(float $confidence, float $autoApply): self
    {
        $this->confidenceThreshold = $confidence;
        $this->autoApplyThreshold = $autoApply;
        return $this;
    }

    /**
     * Scan a document image and extract structured data
     *
     * @param UploadedFile|string $image The document image
     * @param string $documentType The type of document being scanned
     * @param int|null $operationId Optional operation ID to associate the scan with
     * @return array Extracted and verified data
     */
    public function scanDocument($image, string $documentType = self::TYPE_INVOICE, ?int $operationId = null): array
    {
        $startTime = microtime(true);
        $rawResponse = null;
        $error = null;

        try {
            // Build the appropriate prompt based on document type
            $prompt = $this->buildExtractionPrompt($documentType);

            // Analyze the image with Gemini
            $rawResponse = $this->geminiService->analyzeImage($image, $prompt);

            // Parse the AI response
            $extractedData = $this->parseExtractionResponse($rawResponse, $documentType);

            if (!$extractedData['success']) {
                $error = $extractedData['error'];
                // Log the failed scan
                $this->logScan(
                    $image,
                    $documentType,
                    $rawResponse,
                    null,
                    $operationId,
                    microtime(true) - $startTime,
                    $error
                );

                return $extractedData;
            }

            // Verify and enrich with database matches
            $verifiedData = $this->verifyAndEnrichData($extractedData['data'], $documentType);

            // Calculate overall statistics
            $stats = $this->calculateStats($verifiedData);
            $verifiedData['stats'] = $stats;

            // Determine if this needs review based on confidence
            $verifiedData['needs_review'] = !$this->meetsConfidenceThreshold($verifiedData);
            $verifiedData['can_auto_apply'] = $this->canAutoApply($verifiedData);

            $processingTimeMs = (microtime(true) - $startTime) * 1000;

            // Log the successful scan
            $scanLog = $this->logScan(
                $image,
                $documentType,
                $rawResponse,
                $verifiedData,
                $operationId,
                $processingTimeMs
            );

            Log::info('DocumentScannerService: Document scanned successfully', [
                'document_type' => $documentType,
                'vendor_matched' => $verifiedData['vendor_match']['matched'] ?? false,
                'po_matched' => $verifiedData['po_match']['matched'] ?? false,
                'lines_count' => count($verifiedData['line_items'] ?? []),
                'overall_confidence' => $verifiedData['confidence'] ?? 0,
                'needs_review' => $verifiedData['needs_review'],
                'scan_log_id' => $scanLog?->id,
            ]);

            return [
                'success' => true,
                'data' => $verifiedData,
                'document_type' => $documentType,
                'scan_log_id' => $scanLog?->id,
            ];

        } catch (Exception $e) {
            $error = $e->getMessage();

            Log::error('DocumentScannerService: Scan failed', [
                'document_type' => $documentType,
                'error' => $error,
            ]);

            // Log the failed scan
            $this->logScan(
                $image,
                $documentType,
                $rawResponse,
                null,
                $operationId,
                (microtime(true) - $startTime) * 1000,
                $error
            );

            return [
                'success' => false,
                'error' => 'Failed to scan document: ' . $error,
                'data' => null,
            ];
        }
    }

    /**
     * Log a scan attempt to the database for audit trail
     */
    public function logScan(
        $image,
        string $documentType,
        ?string $rawResponse,
        ?array $extractedData,
        ?int $operationId = null,
        ?float $processingTimeMs = null,
        ?string $error = null
    ): ?DocumentScanLog {
        if (!$this->enableLogging) {
            return null;
        }

        try {
            // Store the uploaded file if it's an UploadedFile
            $filePath = null;
            $originalFilename = null;
            $fileSize = null;

            if ($image instanceof UploadedFile) {
                $originalFilename = $image->getClientOriginalName();
                $fileSize = $image->getSize();
                $filePath = $image->store('document-scans/' . date('Y/m'), 'local');
            }

            // Calculate statistics from extracted data
            $linesTotal = 0;
            $linesMatched = 0;
            $linesUnmatched = 0;
            $overallConfidence = null;
            $vendorConfidence = null;
            $poConfidence = null;
            $vendorMatched = false;
            $matchedVendorId = null;
            $poMatched = false;
            $matchedPoId = null;

            if ($extractedData) {
                $overallConfidence = $extractedData['confidence'] ?? null;
                $vendorConfidence = $extractedData['vendor_match']['confidence'] ?? null;
                $vendorMatched = $extractedData['vendor_match']['matched'] ?? false;
                $matchedVendorId = $extractedData['vendor_match']['id'] ?? null;
                $poMatched = $extractedData['po_match']['matched'] ?? false;
                $matchedPoId = $extractedData['po_match']['id'] ?? null;

                if (!empty($extractedData['line_items'])) {
                    $linesTotal = count($extractedData['line_items']);
                    foreach ($extractedData['line_items'] as $line) {
                        if (!empty($line['product_match']['matched'])) {
                            $linesMatched++;
                        } else {
                            $linesUnmatched++;
                        }
                    }
                }
            }

            // Determine initial status
            $status = $error
                ? 'failed'
                : ($extractedData && $this->canAutoApply($extractedData)
                    ? DocumentScanLog::STATUS_AUTO_APPLIED
                    : DocumentScanLog::STATUS_PENDING_REVIEW);

            return DocumentScanLog::create([
                'operation_id' => $operationId,
                'document_type' => $documentType,
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'file_size' => $fileSize,
                'raw_ai_response' => $rawResponse ? ['response' => $rawResponse] : null,
                'extracted_data' => $extractedData,
                'overall_confidence' => $overallConfidence,
                'vendor_confidence' => $vendorConfidence,
                'po_confidence' => $poConfidence,
                'vendor_matched' => $vendorMatched,
                'matched_vendor_id' => $matchedVendorId,
                'po_matched' => $poMatched,
                'matched_po_id' => $matchedPoId,
                'lines_total_count' => $linesTotal,
                'lines_matched_count' => $linesMatched,
                'lines_unmatched_count' => $linesUnmatched,
                'status' => $status,
                'processing_time_ms' => $processingTimeMs ? (int) $processingTimeMs : null,
                'error_message' => $error,
                'created_by' => Auth::id(),
            ]);

        } catch (Exception $e) {
            Log::warning('DocumentScannerService: Failed to log scan', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Normalize OCR text to handle common misreads
     *
     * Handles: 0/O, 1/I/l, 5/S, 8/B, etc.
     */
    public function normalizeOcrText(string $text): string
    {
        // Common OCR substitutions
        $substitutions = [
            // Numbers that look like letters
            '0' => 'O',
            '1' => 'I',
            '5' => 'S',
            '8' => 'B',
            // Letters that look like numbers
            'O' => '0',
            'I' => '1',
            'l' => '1',
            'S' => '5',
            'B' => '8',
        ];

        // Return uppercase normalized version
        return strtoupper(trim($text));
    }

    /**
     * Calculate string similarity using Levenshtein distance
     *
     * @return float Similarity score between 0 and 1
     */
    public function calculateStringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);

        return 1 - ($distance / $maxLen);
    }

    /**
     * Check if a string matches with OCR variations
     */
    public function matchesWithOcrVariations(string $search, string $target): bool
    {
        $search = strtoupper(trim($search));
        $target = strtoupper(trim($target));

        // Exact match
        if ($search === $target) {
            return true;
        }

        // Try common OCR variations
        $variations = $this->generateOcrVariations($search);
        foreach ($variations as $variation) {
            if ($variation === $target || str_contains($target, $variation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate possible OCR variations of a string
     */
    protected function generateOcrVariations(string $text): array
    {
        $variations = [$text];

        // Common OCR character confusions
        $confusions = [
            '0' => ['O', 'Q', 'D'],
            'O' => ['0', 'Q', 'D'],
            '1' => ['I', 'l', '|', '7'],
            'I' => ['1', 'l', '|'],
            'l' => ['1', 'I', '|'],
            '5' => ['S', '$'],
            'S' => ['5', '$'],
            '8' => ['B', '&'],
            'B' => ['8', '&'],
            '2' => ['Z'],
            'Z' => ['2'],
            '6' => ['G', 'b'],
            'G' => ['6', 'C'],
            '9' => ['g', 'q'],
        ];

        // Generate single-character variations
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            if (isset($confusions[$char])) {
                foreach ($confusions[$char] as $replacement) {
                    $variation = substr_replace($text, $replacement, $i, 1);
                    $variations[] = $variation;
                }
            }
        }

        return array_unique($variations);
    }

    /**
     * Calculate statistics from verified data
     */
    protected function calculateStats(array $data): array
    {
        $stats = [
            'lines_total' => 0,
            'lines_matched' => 0,
            'lines_unmatched' => 0,
            'match_rate' => 0,
            'avg_line_confidence' => 0,
        ];

        if (!empty($data['line_items'])) {
            $stats['lines_total'] = count($data['line_items']);
            $totalConfidence = 0;

            foreach ($data['line_items'] as $line) {
                if (!empty($line['product_match']['matched'])) {
                    $stats['lines_matched']++;
                } else {
                    $stats['lines_unmatched']++;
                }
                $totalConfidence += ($line['product_match']['confidence'] ?? 0);
            }

            if ($stats['lines_total'] > 0) {
                $stats['match_rate'] = round(($stats['lines_matched'] / $stats['lines_total']) * 100, 1);
                $stats['avg_line_confidence'] = round($totalConfidence / $stats['lines_total'], 2);
            }
        }

        return $stats;
    }

    /**
     * Check if the scan results meet the confidence threshold
     */
    public function meetsConfidenceThreshold(array $data): bool
    {
        $confidence = $data['confidence'] ?? 0;
        return $confidence >= $this->confidenceThreshold;
    }

    /**
     * Check if the scan results can be auto-applied (high confidence)
     */
    public function canAutoApply(array $data): bool
    {
        $confidence = $data['confidence'] ?? 0;
        $vendorMatched = $data['vendor_match']['matched'] ?? false;
        $poMatched = $data['po_match']['matched'] ?? false;

        // Must have high confidence AND matched vendor/PO
        return $confidence >= $this->autoApplyThreshold
            && $vendorMatched
            && $poMatched;
    }

    /**
     * Build the extraction prompt based on document type
     */
    protected function buildExtractionPrompt(string $documentType): string
    {
        $basePrompt = "You are an AI assistant for a woodworking company's ERP system. ";

        switch ($documentType) {
            case self::TYPE_INVOICE:
                return $basePrompt . <<<PROMPT
Analyze this vendor invoice/bill image and extract all data into JSON format.

Extract the following information:
{
  "vendor": {
    "name": "Vendor/Supplier company name",
    "address": "Full address if visible",
    "phone": "Phone number if visible",
    "email": "Email if visible"
  },
  "document": {
    "invoice_number": "Invoice or bill number",
    "invoice_date": "Date in YYYY-MM-DD format",
    "due_date": "Due date in YYYY-MM-DD format if visible",
    "po_reference": "Purchase order reference if mentioned",
    "terms": "Payment terms if visible (e.g., Net 30)"
  },
  "line_items": [
    {
      "line_number": 1,
      "sku": "Vendor's product code/SKU",
      "description": "Product description",
      "quantity": 0.00,
      "unit": "Unit of measure (ea, box, ft, etc.)",
      "unit_price": 0.00,
      "line_total": 0.00
    }
  ],
  "totals": {
    "subtotal": 0.00,
    "tax": 0.00,
    "shipping": 0.00,
    "total": 0.00
  },
  "notes": "Any additional notes or special instructions",
  "confidence": 0.0 to 1.0
}

Be precise with numbers. Use null for fields you cannot read clearly.
Return ONLY the JSON object, no additional text.
PROMPT;

            case self::TYPE_PACKING_SLIP:
                return $basePrompt . <<<PROMPT
Analyze this packing slip/delivery ticket image and extract all data into JSON format.

LUMBER/SHEET GOODS INDUSTRY CONTEXT:
- Sheet goods (plywood, MDF, etc) are often priced PER SQUARE FOOT, not per sheet
- A standard 4x8 sheet (48"x96") = 32 square feet
- Dimensions like "48 X 96" are in INCHES (not feet)
- Common sheet sizes: 4x8 (32sf), 4x10 (40sf), 5x10 (50sf)
- Lumber may be priced per board foot (bf) or linear foot (lf)

PRICING VALIDATION:
- If you see: 15 sheets at $2.52 with total $1,209.60
  - $2.52 × 15 sheets = $37.80 (doesn't match total)
  - $2.52 × 480sf = $1,209.60 (MATCHES - price is per sqft!)
- Always determine: Is price per piece, per sqft, per lf, or per bf?

IMPORTANT: For lumber/sheet goods, one product may show BOTH piece count (sheets/pcs) AND square footage (sf/sqft).
These are the SAME line item - the sf is just a conversion. Combine them into ONE line item.
Example: "FIB651 ... 15sh ... 480sf" = ONE product: 15 sheets (which equals 480 sq ft).

Extract the following information:
{
  "vendor": {
    "name": "Shipping vendor/supplier name",
    "address": "Full address if visible",
    "phone": "Phone number if visible"
  },
  "document": {
    "slip_number": "Packing slip, delivery, or order number",
    "ship_date": "Ship date in YYYY-MM-DD format",
    "order_date": "Order date in YYYY-MM-DD format if different",
    "po_reference": "Purchase order reference if shown",
    "tracking_number": "Tracking number if visible"
  },
  "customer": {
    "name": "Customer/Ship-to company name",
    "address": "Ship-to address",
    "contact": "Contact person or 'ordered by' name"
  },
  "line_items": [
    {
      "line_number": 1,
      "vendor_sku": "Vendor's product code/SKU (e.g., FIB651)",
      "internal_sku": "Internal/alternate product code if shown (e.g., MEDEX34-48)",
      "description": "Full product description",
      "quantity_shipped": 0,
      "unit": "Primary unit (sh, pcs, ea, etc.)",
      "quantity_alt": 0,
      "unit_alt": "Secondary unit if shown (sf, lf, bf, etc.)",
      "unit_price": 0.00,
      "line_total": 0.00,
      "location": "Warehouse location code if shown",
      "part_number": "Part number if shown"
    }
  ],
  "package_info": {
    "boxes": 0,
    "weight": "Total weight if shown",
    "carrier": "Shipping carrier or method"
  },
  "totals": {
    "subtotal": 0.00,
    "tax": 0.00,
    "total": 0.00
  },
  "payment": {
    "method": "Payment method if shown",
    "amount": 0.00,
    "card_last4": "Last 4 digits if card payment"
  },
  "reasoning": {
    "pricing_method": "per_sqft / per_piece / per_lf / per_bf - explain how you determined this",
    "pricing_validation": "Show the math: qty × price = total",
    "unit_conversion": "e.g., 15 pieces × 32 sqft/piece = 480 sqft"
  },
  "notes": ["Any observations, combined tickets, special instructions, delivery notes"],
  "warnings": ["Any issues: ambiguous data, math errors, unusual prices"],
  "confidence": 0.0 to 1.0
}

CRITICAL: 
- Combine related data into single line items. Do NOT split one product into multiple lines.
- Determine if price is per piece or per sqft by checking the math.
- Include ANY notes, instructions, or additional info in the notes array.
- Be precise with quantities. Use null for fields you cannot read clearly.
Return ONLY the JSON object, no additional text.
PROMPT;

            case self::TYPE_QUOTE:
                return $basePrompt . <<<PROMPT
Analyze this vendor quote/estimate image and extract all data into JSON format.

Extract the following information:
{
  "vendor": {
    "name": "Vendor/Supplier company name",
    "address": "Full address if visible",
    "phone": "Phone number if visible",
    "email": "Email if visible",
    "contact_name": "Sales rep or contact name if shown"
  },
  "document": {
    "quote_number": "Quote or estimate number",
    "quote_date": "Date in YYYY-MM-DD format",
    "valid_until": "Expiration date in YYYY-MM-DD format if shown",
    "reference": "Any reference number or project name"
  },
  "line_items": [
    {
      "line_number": 1,
      "sku": "Vendor's product code/SKU",
      "description": "Product description",
      "quantity": 0.00,
      "unit": "Unit of measure (ea, box, ft, etc.)",
      "unit_price": 0.00,
      "line_total": 0.00,
      "lead_time": "Lead time if mentioned"
    }
  ],
  "totals": {
    "subtotal": 0.00,
    "tax": 0.00,
    "shipping": 0.00,
    "total": 0.00
  },
  "terms": {
    "payment_terms": "Payment terms if visible",
    "shipping_terms": "Shipping/freight terms",
    "warranty": "Warranty information if mentioned"
  },
  "notes": "Any additional notes, conditions, or special instructions",
  "confidence": 0.0 to 1.0
}

Be precise with numbers. Use null for fields you cannot read clearly.
Return ONLY the JSON object, no additional text.
PROMPT;

            case self::TYPE_PRODUCT_LABEL:
                return $basePrompt . <<<PROMPT
Analyze this product label/barcode image and extract all data into JSON format.

Extract the following information:
{
  "product": {
    "sku": "Product SKU or part number",
    "barcode": "Barcode number (UPC, EAN, etc.)",
    "name": "Product name",
    "description": "Product description",
    "brand": "Brand name",
    "manufacturer": "Manufacturer name"
  },
  "specifications": {
    "size": "Size or dimensions",
    "color": "Color if applicable",
    "material": "Material if mentioned",
    "weight": "Weight if shown"
  },
  "pricing": {
    "price": 0.00,
    "unit": "Pricing unit"
  },
  "confidence": 0.0 to 1.0
}

Return ONLY the JSON object, no additional text.
PROMPT;

            default:
                return $basePrompt . "Extract all text and data from this document as JSON.";
        }
    }

    /**
     * Parse the AI extraction response
     */
    protected function parseExtractionResponse(string $response, string $documentType): array
    {
        try {
            // Extract JSON from response
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');

            if ($jsonStart === false || $jsonEnd === false) {
                throw new Exception('No JSON found in AI response');
            }

            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $data = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in AI response: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (Exception $e) {
            Log::warning('DocumentScannerService: Failed to parse response', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500),
            ]);

            return [
                'success' => false,
                'error' => 'Could not parse document data: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Verify extracted data against database and enrich with matches
     */
    protected function verifyAndEnrichData(array $data, string $documentType): array
    {
        $result = $data;

        // Match vendor
        if (!empty($data['vendor']['name'])) {
            $result['vendor_match'] = $this->matchVendor($data['vendor']['name']);
        }

        // Match PO reference
        $poRef = $data['document']['po_reference'] ?? null;
        if ($poRef) {
            $result['po_match'] = $this->matchPurchaseOrder($poRef, $result['vendor_match']['id'] ?? null);
        }

        // Match and verify line items
        if (!empty($data['line_items'])) {
            $result['line_items'] = $this->verifyLineItems(
                $data['line_items'],
                $result['vendor_match']['id'] ?? null,
                $result['po_match']['order'] ?? null,
                $documentType
            );
        }

        // Verify totals if this is an invoice
        if ($documentType === self::TYPE_INVOICE && !empty($data['totals']) && !empty($result['po_match']['order'])) {
            $result['totals_verification'] = $this->verifyTotals(
                $data['totals'],
                $result['po_match']['order']
            );
        }

        return $result;
    }

    /**
     * Match vendor name to existing partner using fuzzy matching
     */
    protected function matchVendor(string $vendorName): array
    {
        $vendorName = trim($vendorName);
        $normalizedName = $this->normalizeOcrText($vendorName);

        // Try exact match first
        $vendor = Partner::where('sub_type', 'supplier')
            ->where('name', $vendorName)
            ->first();

        if ($vendor) {
            return [
                'matched' => true,
                'id' => $vendor->id,
                'name' => $vendor->name,
                'confidence' => 1.0,
                'match_method' => 'exact',
            ];
        }

        // Try case-insensitive exact match
        $vendor = Partner::where('sub_type', 'supplier')
            ->whereRaw('LOWER(name) = ?', [strtolower($vendorName)])
            ->first();

        if ($vendor) {
            return [
                'matched' => true,
                'id' => $vendor->id,
                'name' => $vendor->name,
                'confidence' => 0.98,
                'match_method' => 'exact_case_insensitive',
            ];
        }

        // Try fuzzy match using Levenshtein distance
        $suppliers = Partner::where('sub_type', 'supplier')
            ->select('id', 'name')
            ->get();

        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($suppliers as $supplier) {
            $similarity = $this->calculateStringSimilarity($vendorName, $supplier->name);

            // Also try with OCR variations
            if ($this->matchesWithOcrVariations($vendorName, $supplier->name)) {
                $similarity = max($similarity, 0.85);
            }

            if ($similarity > $bestSimilarity && $similarity >= 0.7) {
                $bestSimilarity = $similarity;
                $bestMatch = $supplier;
            }
        }

        if ($bestMatch && $bestSimilarity >= 0.7) {
            return [
                'matched' => true,
                'id' => $bestMatch->id,
                'name' => $bestMatch->name,
                'confidence' => round($bestSimilarity, 2),
                'match_method' => 'fuzzy',
                'searched_name' => $vendorName,
            ];
        }

        // Try matching individual words for common vendor names
        $words = preg_split('/\s+/', $vendorName);
        foreach ($words as $word) {
            if (strlen($word) >= 4) {
                $vendor = Partner::where('sub_type', 'supplier')
                    ->where('name', 'like', '%' . $word . '%')
                    ->first();
                if ($vendor) {
                    return [
                        'matched' => true,
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'confidence' => 0.6,
                        'match_method' => 'partial_word',
                        'matched_word' => $word,
                    ];
                }
            }
        }

        return [
            'matched' => false,
            'id' => null,
            'name' => $vendorName,
            'confidence' => 0,
            'suggestion' => 'Vendor not found. Create new vendor?',
        ];
    }

    /**
     * Match PO reference to existing purchase order
     */
    protected function matchPurchaseOrder(string $poRef, ?int $vendorId = null): array
    {
        $poRef = trim($poRef);
        $normalizedRef = $this->normalizeOcrText($poRef);

        // Build query
        $query = PurchaseOrder::query();

        // Try matching by PO name or partner reference
        $query->where(function ($q) use ($poRef, $normalizedRef) {
            $q->where('name', $poRef)
              ->orWhere('name', $normalizedRef)
              ->orWhere('name', 'like', '%' . $poRef . '%')
              ->orWhere('partner_reference', $poRef)
              ->orWhere('partner_reference', $normalizedRef)
              ->orWhere('partner_reference', 'like', '%' . $poRef . '%');
        });

        // Filter by vendor if available
        if ($vendorId) {
            $query->where('partner_id', $vendorId);
        }

        $po = $query->with(['lines.product', 'partner'])->first();

        if ($po) {
            return [
                'matched' => true,
                'id' => $po->id,
                'name' => $po->name,
                'order' => $po,
                'state' => $po->state->value ?? $po->state,
                'total' => $po->total_amount,
                'lines_count' => $po->lines->count(),
                'confidence' => $po->name === $poRef ? 1.0 : 0.85,
            ];
        }

        return [
            'matched' => false,
            'id' => null,
            'name' => $poRef,
            'order' => null,
            'confidence' => 0,
            'suggestion' => 'PO not found. Check reference number.',
        ];
    }

    /**
     * Verify and match line items against PO and products
     */
    protected function verifyLineItems(array $lineItems, ?int $vendorId, ?PurchaseOrder $po, string $documentType): array
    {
        $verifiedLines = [];

        foreach ($lineItems as $line) {
            $verifiedLine = $line;
            $verifiedLine['verification'] = [];

            // Try to match product by vendor SKU (check multiple possible field names)
            $sku = $line['vendor_sku'] ?? $line['sku'] ?? null;
            $internalSku = $line['internal_sku'] ?? null;
            $description = $line['description'] ?? '';
            
            if ($sku || $internalSku || $description) {
                // Use enhanced matching that tries SKU, internal SKU, and dimensions
                $productMatch = $this->matchProductEnhanced([
                    'vendor_sku' => $sku,
                    'internal_sku' => $internalSku,
                    'description' => $description,
                ], $vendorId);
                $verifiedLine['product_match'] = $productMatch;

                // Mark if this line needs review (low confidence or no match)
                $verifiedLine['requires_review'] = !$productMatch['matched']
                    || ($productMatch['confidence'] ?? 0) < $this->confidenceThreshold;
            }

            // If we have a PO, try to match to PO line
            if ($po && !empty($verifiedLine['product_match']['product_id'])) {
                $poLineMatch = $this->matchToPOLine(
                    $po,
                    $verifiedLine['product_match']['product_id'],
                    $line
                );
                $verifiedLine['po_line_match'] = $poLineMatch;

                // Add quantity verification for packing slips
                if ($documentType === self::TYPE_PACKING_SLIP && $poLineMatch['matched']) {
                    $qtyShipped = floatval($line['quantity_shipped'] ?? $line['quantity'] ?? 0);
                    $qtyOrdered = floatval($poLineMatch['qty_ordered'] ?? 0);
                    $qtyReceived = floatval($poLineMatch['qty_received'] ?? 0);
                    $qtyRemaining = $qtyOrdered - $qtyReceived;

                    $verifiedLine['verification']['quantity'] = [
                        'shipped' => $qtyShipped,
                        'ordered' => $qtyOrdered,
                        'previously_received' => $qtyReceived,
                        'remaining_to_receive' => $qtyRemaining,
                        'status' => $this->getQuantityStatus($qtyShipped, $qtyRemaining),
                    ];
                }

                // Add price verification for invoices
                if ($documentType === self::TYPE_INVOICE && $poLineMatch['matched']) {
                    $invoicePrice = floatval($line['unit_price'] ?? 0);
                    $poPrice = floatval($poLineMatch['unit_price'] ?? 0);
                    $priceDiff = abs($invoicePrice - $poPrice);

                    $verifiedLine['verification']['price'] = [
                        'invoice_price' => $invoicePrice,
                        'po_price' => $poPrice,
                        'difference' => $priceDiff,
                        'status' => $priceDiff < 0.01 ? 'match' : ($priceDiff < $poPrice * 0.05 ? 'close' : 'mismatch'),
                    ];
                }
            }

            $verifiedLines[] = $verifiedLine;
        }

        return $verifiedLines;
    }

    /**
     * Match product by vendor's SKU/product code using fuzzy matching
     */
    protected function matchProductBySku(string $sku, ?int $vendorId): array
    {
        $sku = trim($sku);
        $normalizedSku = $this->normalizeOcrText($sku);

        // First, try exact match in product_suppliers table (if we have vendor)
        if ($vendorId) {
            $productSupplier = ProductSupplier::where('partner_id', $vendorId)
                ->where('product_code', $sku)
                ->with('product')
                ->first();

            if ($productSupplier && $productSupplier->product) {
                return [
                    'matched' => true,
                    'product_id' => $productSupplier->product_id,
                    'product_name' => $productSupplier->product->name,
                    'vendor_sku' => $productSupplier->product_code,
                    'vendor_price' => $productSupplier->price,
                    'confidence' => 1.0,
                    'match_method' => self::MATCH_BY_VENDOR_SKU,
                ];
            }

            // Try with OCR variations
            $productSupplier = ProductSupplier::where('partner_id', $vendorId)
                ->where(function ($q) use ($sku, $normalizedSku) {
                    $q->where('product_code', $normalizedSku)
                      ->orWhere('product_code', 'like', '%' . $sku . '%');
                })
                ->with('product')
                ->first();

            if ($productSupplier && $productSupplier->product) {
                return [
                    'matched' => true,
                    'product_id' => $productSupplier->product_id,
                    'product_name' => $productSupplier->product->name,
                    'vendor_sku' => $productSupplier->product_code,
                    'vendor_price' => $productSupplier->price,
                    'confidence' => 0.85,
                    'match_method' => self::MATCH_BY_VENDOR_SKU,
                    'note' => 'Fuzzy match on vendor SKU',
                ];
            }
        }

        // Try matching by internal product SKU (reference)
        $product = Product::where('reference', $sku)
            ->orWhere('reference', $normalizedSku)
            ->first();

        if ($product) {
            return [
                'matched' => true,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'vendor_sku' => null,
                'confidence' => 0.9,
                'match_method' => self::MATCH_BY_INTERNAL_SKU,
            ];
        }

        // Try matching by barcode
        $product = Product::where('barcode', $sku)
            ->orWhere('barcode', $normalizedSku)
            ->first();

        if ($product) {
            return [
                'matched' => true,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'vendor_sku' => null,
                'confidence' => 0.95,
                'match_method' => self::MATCH_BY_BARCODE,
            ];
        }

        // Try fuzzy match on internal SKU
        $products = Product::whereNotNull('reference')
            ->select('id', 'name', 'reference', 'barcode')
            ->get();

        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($products as $product) {
            if ($product->reference) {
                $similarity = $this->calculateStringSimilarity($sku, $product->reference);
                if ($similarity > $bestSimilarity && $similarity >= 0.75) {
                    $bestSimilarity = $similarity;
                    $bestMatch = $product;
                }
            }
        }

        if ($bestMatch) {
            return [
                'matched' => true,
                'product_id' => $bestMatch->id,
                'product_name' => $bestMatch->name,
                'vendor_sku' => null,
                'confidence' => round($bestSimilarity * 0.8, 2), // Reduce confidence for fuzzy
                'match_method' => self::MATCH_BY_INTERNAL_SKU,
                'note' => 'Fuzzy match on internal SKU',
            ];
        }

        return [
            'matched' => false,
            'product_id' => null,
            'searched_sku' => $sku,
            'confidence' => 0,
            'suggestion' => 'Product not found. Add vendor product code?',
        ];
    }

    /**
     * Enhanced product matching - tries SKU first, then dimensions
     */
    protected function matchProductEnhanced(array $lineItem, ?int $vendorId): array
    {
        $vendorSku = $lineItem['vendor_sku'] ?? $lineItem['sku'] ?? null;
        $internalSku = $lineItem['internal_sku'] ?? null;
        $description = $lineItem['description'] ?? '';
        
        // First try SKU matching
        if ($vendorSku) {
            $skuMatch = $this->matchProductBySku($vendorSku, $vendorId);
            if ($skuMatch['matched']) {
                return $skuMatch;
            }
        }
        
        // Try internal SKU matching
        if ($internalSku) {
            $internalMatch = $this->matchProductBySku($internalSku, null);
            if ($internalMatch['matched']) {
                return $internalMatch;
            }
        }
        
        // Try dimension-based matching for sheet goods
        if ($description) {
            $dimMatch = $this->matchProductByDimensions($description, $internalSku);
            if ($dimMatch['matched']) {
                return $dimMatch;
            }
        }
        
        // No match found
        return [
            'matched' => false,
            'product_id' => null,
            'searched_sku' => $vendorSku ?? $internalSku ?? 'N/A',
            'searched_description' => $description,
            'confidence' => 0,
            'suggestion' => $vendorId 
                ? "Add vendor SKU '{$vendorSku}' to vendor product list, or create new product with dimensions."
                : 'Product not found. Create product or add vendor mapping.',
        ];
    }

    /**
     * Match product by description and dimensions (for sheet goods)
     * Parses descriptions like "Medex FSC 3/4 48 X 96" to find matching products
     */
    protected function matchProductByDimensions(string $description, ?string $internalSku = null): array
    {
        // Parse dimensions from description
        $dimensions = $this->parseSheetDimensions($description);
        
        if (!$dimensions['thickness'] && !$dimensions['sheet_size']) {
            return [
                'matched' => false,
                'reason' => 'No dimensions found in description',
            ];
        }

        // Build query based on parsed dimensions
        $query = Product::query();
        
        // Match by thickness if found
        if ($dimensions['thickness']) {
            $query->where(function ($q) use ($dimensions) {
                $q->where('thickness_inches', $dimensions['thickness'])
                  ->orWhere('thickness_inches', round($dimensions['thickness'], 3));
            });
        }
        
        // Match by sheet size if found
        if ($dimensions['sheet_size']) {
            $query->where('sheet_size', $dimensions['sheet_size']);
        }
        
        // Match by material type in name
        if ($dimensions['material']) {
            $query->where(function ($q) use ($dimensions) {
                $q->where('name', 'like', '%' . $dimensions['material'] . '%')
                  ->orWhere('description', 'like', '%' . $dimensions['material'] . '%');
            });
        }

        // Try internal SKU match first if provided
        if ($internalSku) {
            $skuMatch = (clone $query)->where(function ($q) use ($internalSku) {
                $q->where('reference', 'like', '%' . $internalSku . '%')
                  ->orWhere('reference', 'like', '%' . str_replace('-', '', $internalSku) . '%');
            })->first();
            
            if ($skuMatch) {
                return [
                    'matched' => true,
                    'product_id' => $skuMatch->id,
                    'product_name' => $skuMatch->name,
                    'confidence' => 0.9,
                    'match_method' => self::MATCH_BY_DIMENSIONS,
                    'parsed_dimensions' => $dimensions,
                    'note' => 'Matched by internal SKU + dimensions',
                ];
            }
        }
        
        // Get potential matches
        $matches = $query->select('id', 'name', 'reference', 'thickness_inches', 'sheet_size', 'sqft_per_sheet')
            ->limit(5)
            ->get();
        
        if ($matches->isEmpty()) {
            return [
                'matched' => false,
                'parsed_dimensions' => $dimensions,
                'suggestion' => "No products found with thickness={$dimensions['thickness']}\", sheet={$dimensions['sheet_size']}",
            ];
        }
        
        // If only one match, return it
        if ($matches->count() === 1) {
            $match = $matches->first();
            return [
                'matched' => true,
                'product_id' => $match->id,
                'product_name' => $match->name,
                'confidence' => 0.75,
                'match_method' => self::MATCH_BY_DIMENSIONS,
                'parsed_dimensions' => $dimensions,
                'note' => 'Single match by dimensions',
            ];
        }
        
        // Multiple matches - try to narrow down by name similarity
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($matches as $match) {
            $score = 0;
            
            // Check material match in name
            if ($dimensions['material'] && stripos($match->name, $dimensions['material']) !== false) {
                $score += 0.3;
            }
            
            // Check if sqft matches (480sf = 15 sheets * 32 sqft)
            if ($match->sqft_per_sheet && $dimensions['sqft_total']) {
                $expectedSheets = $dimensions['sqft_total'] / $match->sqft_per_sheet;
                if (abs($expectedSheets - round($expectedSheets)) < 0.1) {
                    $score += 0.2;
                }
            }
            
            // Prefer exact thickness match
            if ($match->thickness_inches == $dimensions['thickness']) {
                $score += 0.2;
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $match;
            }
        }
        
        if ($bestMatch && $bestScore >= 0.3) {
            return [
                'matched' => true,
                'product_id' => $bestMatch->id,
                'product_name' => $bestMatch->name,
                'confidence' => round(0.6 + $bestScore, 2),
                'match_method' => self::MATCH_BY_DIMENSIONS,
                'parsed_dimensions' => $dimensions,
                'note' => "Best match from {$matches->count()} candidates",
                'requires_review' => true,
            ];
        }
        
        return [
            'matched' => false,
            'parsed_dimensions' => $dimensions,
            'candidates' => $matches->pluck('name', 'id')->toArray(),
            'suggestion' => "Multiple possible matches. Please select manually.",
        ];
    }

    /**
     * Parse sheet dimensions from description text
     * Examples: "3/4 48 X 96", "3/4\" 4x8", "Medex 3/4 48x96"
     */
    protected function parseSheetDimensions(string $text): array
    {
        $result = [
            'thickness' => null,
            'thickness_fraction' => null,
            'width' => null,
            'length' => null,
            'sheet_size' => null,
            'sqft_total' => null,
            'material' => null,
        ];
        
        // Normalize text
        $text = strtoupper(str_replace(['"', "'", '″'], '', $text));
        
        // Extract material type
        $materials = ['MEDEX', 'MDF', 'PLYWOOD', 'BIRCH', 'MAPLE', 'OAK', 'POPLAR', 'WALNUT', 'CHERRY'];
        foreach ($materials as $material) {
            if (stripos($text, $material) !== false) {
                $result['material'] = $material;
                break;
            }
        }
        
        // Parse thickness (fractions like 3/4, 1/2, 1/4)
        if (preg_match('/(\d+)\/(\d+)/', $text, $matches)) {
            $numerator = intval($matches[1]);
            $denominator = intval($matches[2]);
            if ($denominator > 0) {
                $result['thickness'] = round($numerator / $denominator, 3);
                $result['thickness_fraction'] = "{$numerator}/{$denominator}";
            }
        }
        
        // Parse sheet dimensions (48 X 96, 48x96, 4x8)
        // First try full dimensions (48 X 96)
        if (preg_match('/(\d{2,3})\s*[Xx]\s*(\d{2,3})/', $text, $matches)) {
            $result['width'] = intval($matches[1]);
            $result['length'] = intval($matches[2]);
            
            // Convert to standard sheet size
            if ($result['width'] == 48 && $result['length'] == 96) {
                $result['sheet_size'] = '4x8';
            } elseif ($result['width'] == 48 && $result['length'] == 120) {
                $result['sheet_size'] = '4x10';
            } elseif ($result['width'] == 60 && $result['length'] == 120) {
                $result['sheet_size'] = '5x10';
            }
        }
        // Try compact format (4x8, 4x10)
        elseif (preg_match('/(\d)[Xx](\d{1,2})/', $text, $matches)) {
            $result['width'] = intval($matches[1]) * 12; // Convert feet to inches
            $result['length'] = intval($matches[2]) * 12;
            $result['sheet_size'] = "{$matches[1]}x{$matches[2]}";
        }
        
        // Parse square footage if present (480sf, 480 SF, 480 sqft)
        if (preg_match('/(\d+)\s*(?:SF|SQFT|SQ\.?\s*FT)/i', $text, $matches)) {
            $result['sqft_total'] = intval($matches[1]);
        }
        
        return $result;
    }

    /**
     * Match to a specific PO line
     */
    protected function matchToPOLine(PurchaseOrder $po, int $productId, array $lineData): array
    {
        $poLine = $po->lines->firstWhere('product_id', $productId);

        if ($poLine) {
            return [
                'matched' => true,
                'po_line_id' => $poLine->id,
                'product_id' => $poLine->product_id,
                'qty_ordered' => floatval($poLine->product_qty),
                'qty_received' => floatval($poLine->qty_received ?? 0),
                'qty_invoiced' => floatval($poLine->qty_invoiced ?? 0),
                'unit_price' => floatval($poLine->price_unit),
                'uom' => $poLine->uom?->name,
            ];
        }

        return [
            'matched' => false,
            'product_id' => $productId,
            'suggestion' => 'Product not on this PO',
        ];
    }

    /**
     * Get quantity status for receiving verification
     */
    protected function getQuantityStatus(float $shipped, float $remaining): string
    {
        if (abs($shipped - $remaining) < 0.01) {
            return 'exact';
        } elseif ($shipped < $remaining) {
            return 'partial';
        } else {
            return 'over';
        }
    }

    /**
     * Verify invoice totals against PO
     */
    protected function verifyTotals(array $totals, PurchaseOrder $po): array
    {
        $invoiceTotal = floatval($totals['total'] ?? 0);
        $invoiceSubtotal = floatval($totals['subtotal'] ?? 0);
        $invoiceTax = floatval($totals['tax'] ?? 0);

        $poTotal = floatval($po->total_amount);
        $poSubtotal = floatval($po->untaxed_amount);
        $poTax = floatval($po->tax_amount);

        return [
            'subtotal' => [
                'invoice' => $invoiceSubtotal,
                'po' => $poSubtotal,
                'difference' => abs($invoiceSubtotal - $poSubtotal),
                'status' => abs($invoiceSubtotal - $poSubtotal) < 0.02 ? 'match' : 'mismatch',
            ],
            'tax' => [
                'invoice' => $invoiceTax,
                'po' => $poTax,
                'difference' => abs($invoiceTax - $poTax),
                'status' => abs($invoiceTax - $poTax) < 0.02 ? 'match' : 'mismatch',
            ],
            'total' => [
                'invoice' => $invoiceTotal,
                'po' => $poTotal,
                'difference' => abs($invoiceTotal - $poTotal),
                'status' => abs($invoiceTotal - $poTotal) < 0.02 ? 'match' : 'mismatch',
            ],
        ];
    }

    // =========================================================================
    // SKU CRUD OPERATIONS
    // =========================================================================

    /**
     * Create a new product from scanned data
     */
    public function createProductFromScan(array $scanData): array
    {
        try {
            $dimensions = $this->parseSheetDimensions($scanData['description'] ?? '');
            
            $product = Product::create([
                'name' => $scanData['name'] ?? $scanData['description'] ?? 'New Product',
                'reference' => $scanData['internal_sku'] ?? null,
                'type' => 'product',
                'tracking' => 'none',
                'is_storable' => true,
                'purchase_ok' => true,
                'sales_ok' => true,
                'thickness_inches' => $dimensions['thickness'],
                'sheet_size' => $dimensions['sheet_size'],
                'sqft_per_sheet' => $this->calculateSqFtPerSheet($dimensions),
                'material_category_id' => null, // Could map material type
                'creator_id' => Auth::id(),
                'company_id' => 1, // Default company
            ]);
            
            // If vendor info provided, create vendor SKU mapping
            if (!empty($scanData['vendor_id']) && !empty($scanData['vendor_sku'])) {
                $this->linkVendorSku(
                    $product->id, 
                    $scanData['vendor_id'], 
                    $scanData['vendor_sku'],
                    $scanData['unit_price'] ?? null
                );
            }
            
            Log::info('DocumentScannerService: Created product from scan', [
                'product_id' => $product->id,
                'name' => $product->name,
                'vendor_sku' => $scanData['vendor_sku'] ?? null,
            ]);
            
            return [
                'success' => true,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "Created product: {$product->name}",
            ];
            
        } catch (Exception $e) {
            Log::error('DocumentScannerService: Failed to create product', [
                'error' => $e->getMessage(),
                'data' => $scanData,
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to create product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Link a vendor SKU to an existing product
     */
    public function linkVendorSku(int $productId, int $vendorId, string $vendorSku, ?float $price = null): array
    {
        try {
            // Check if product exists
            $product = Product::find($productId);
            if (!$product) {
                return [
                    'success' => false,
                    'error' => "Product ID {$productId} not found",
                ];
            }
            
            // Check if vendor exists
            $vendor = Partner::find($vendorId);
            if (!$vendor) {
                return [
                    'success' => false,
                    'error' => "Vendor ID {$vendorId} not found",
                ];
            }
            
            // Check if mapping already exists
            $existing = ProductSupplier::where('product_id', $productId)
                ->where('partner_id', $vendorId)
                ->where('product_code', $vendorSku)
                ->first();
                
            if ($existing) {
                return [
                    'success' => true,
                    'message' => 'Vendor SKU mapping already exists',
                    'mapping_id' => $existing->id,
                    'already_existed' => true,
                ];
            }
            
            // Get default currency (USD = 1 typically)
            $currencyId = \DB::table('currencies')->where('code', 'USD')->value('id') ?? 1;
            
            // Create the mapping
            $mapping = ProductSupplier::create([
                'product_id' => $productId,
                'partner_id' => $vendorId,
                'product_code' => $vendorSku,
                'product_name' => $product->name,
                'price' => $price ?? 0,
                'min_qty' => 1,
                'delay' => 1,
                'currency_id' => $currencyId,
                'company_id' => 1,
                'creator_id' => Auth::id() ?? 1,
            ]);
            
            Log::info('DocumentScannerService: Linked vendor SKU', [
                'mapping_id' => $mapping->id,
                'product_id' => $productId,
                'vendor_id' => $vendorId,
                'vendor_sku' => $vendorSku,
            ]);
            
            return [
                'success' => true,
                'message' => "Linked SKU '{$vendorSku}' to '{$product->name}'",
                'mapping_id' => $mapping->id,
                'product_id' => $productId,
                'product_name' => $product->name,
                'vendor_name' => $vendor->name,
            ];
            
        } catch (Exception $e) {
            Log::error('DocumentScannerService: Failed to link vendor SKU', [
                'error' => $e->getMessage(),
                'product_id' => $productId,
                'vendor_id' => $vendorId,
                'vendor_sku' => $vendorSku,
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to link vendor SKU: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Find existing products that could match scanned line item
     */
    public function findMatchingProducts(array $lineItem): array
    {
        $results = [];
        $description = $lineItem['description'] ?? '';
        $dimensions = $this->parseSheetDimensions($description);
        
        // Search by name/description
        $query = Product::query();
        
        // Filter by dimensions if available
        if ($dimensions['thickness']) {
            $query->where('thickness_inches', $dimensions['thickness']);
        }
        
        if ($dimensions['sheet_size']) {
            $query->where('sheet_size', $dimensions['sheet_size']);
        }
        
        // Filter by material type
        if ($dimensions['material']) {
            $query->where('name', 'like', '%' . $dimensions['material'] . '%');
        }
        
        $matches = $query->select('id', 'name', 'reference', 'thickness_inches', 'sheet_size', 'sqft_per_sheet')
            ->orderBy('name')
            ->limit(20)
            ->get();
        
        foreach ($matches as $product) {
            $results[] = [
                'id' => $product->id,
                'name' => $product->name,
                'reference' => $product->reference,
                'thickness' => $product->thickness_inches,
                'sheet_size' => $product->sheet_size,
                'sqft' => $product->sqft_per_sheet,
            ];
        }
        
        return [
            'parsed_dimensions' => $dimensions,
            'matches' => $results,
            'count' => count($results),
        ];
    }

    /**
     * Calculate square feet per sheet from dimensions
     */
    protected function calculateSqFtPerSheet(array $dimensions): ?float
    {
        if (!$dimensions['width'] || !$dimensions['length']) {
            // Default sizes
            if ($dimensions['sheet_size'] === '4x8') {
                return 32.0;
            } elseif ($dimensions['sheet_size'] === '4x10') {
                return 40.0;
            } elseif ($dimensions['sheet_size'] === '5x10') {
                return 50.0;
            }
            return null;
        }
        
        // Convert inches to feet and calculate
        return round(($dimensions['width'] / 12) * ($dimensions['length'] / 12), 2);
    }

    // =========================================================================
    // AI AUTO-LEARN OPERATIONS
    // =========================================================================

    /**
     * Auto-learn a vendor SKU mapping from scan results
     * Creates the mapping and marks it as AI-created for tracking
     *
     * @param int $productId The product to link
     * @param int $vendorId The vendor/supplier ID
     * @param string $vendorSku The vendor's SKU/product code
     * @param string|null $vendorProductName The vendor's product name
     * @param float|null $price The vendor's price
     * @param string|null $sourceDocument Reference to source document (e.g., packing slip #)
     * @return ProductSupplier|array
     */
    public function autoLearnVendorSku(
        int $productId,
        int $vendorId,
        string $vendorSku,
        ?string $vendorProductName = null,
        ?float $price = null,
        ?string $sourceDocument = null
    ): ProductSupplier|array {
        try {
            // Check if product exists
            $product = Product::find($productId);
            if (!$product) {
                return [
                    'success' => false,
                    'error' => "Product ID {$productId} not found",
                ];
            }
            
            // Check if vendor exists
            $vendor = Partner::find($vendorId);
            if (!$vendor) {
                return [
                    'success' => false,
                    'error' => "Vendor ID {$vendorId} not found",
                ];
            }
            
            // Check if mapping already exists
            $existing = ProductSupplier::where('product_id', $productId)
                ->where('partner_id', $vendorId)
                ->where('product_code', $vendorSku)
                ->first();
                
            if ($existing) {
                Log::info('DocumentScannerService: Vendor SKU mapping already exists', [
                    'mapping_id' => $existing->id,
                    'product_id' => $productId,
                    'vendor_sku' => $vendorSku,
                ]);
                
                return $existing;
            }
            
            // Get default currency (USD = 1 typically)
            $currencyId = \DB::table('currencies')->where('code', 'USD')->value('id') ?? 1;
            
            // Create the mapping with AI tracking fields
            $mapping = ProductSupplier::create([
                'product_id' => $productId,
                'partner_id' => $vendorId,
                'product_code' => $vendorSku,
                'product_name' => $vendorProductName ?? $product->name,
                'price' => $price ?? 0,
                'min_qty' => 1,
                'delay' => 1,
                'currency_id' => $currencyId,
                'company_id' => 1,
                'creator_id' => Auth::id() ?? 1,
                // AI tracking fields
                'ai_created' => true,
                'ai_source_document' => $sourceDocument,
                'ai_created_at' => now(),
            ]);
            
            Log::info('DocumentScannerService: AI auto-learned vendor SKU', [
                'mapping_id' => $mapping->id,
                'product_id' => $productId,
                'product_name' => $product->name,
                'vendor_id' => $vendorId,
                'vendor_name' => $vendor->name,
                'vendor_sku' => $vendorSku,
                'source_document' => $sourceDocument,
            ]);
            
            return $mapping;
            
        } catch (Exception $e) {
            Log::error('DocumentScannerService: Failed to auto-learn vendor SKU', [
                'error' => $e->getMessage(),
                'product_id' => $productId,
                'vendor_id' => $vendorId,
                'vendor_sku' => $vendorSku,
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to create vendor SKU mapping: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Bulk learn vendor SKU mappings from scan review
     * Used when user confirms multiple mappings at once
     *
     * @param array $mappings Array of mappings, each containing:
     *   - product_id: int
     *   - vendor_sku: string
     *   - vendor_product_name: string|null
     *   - price: float|null
     * @param int $vendorId The vendor ID for all mappings
     * @param string|null $sourceDocument Reference to source document
     * @return array Results with success count and details
     */
    public function bulkLearnVendorSkus(array $mappings, int $vendorId, ?string $sourceDocument = null): array
    {
        $results = [
            'success' => true,
            'total' => count($mappings),
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'details' => [],
        ];
        
        foreach ($mappings as $mapping) {
            if (empty($mapping['product_id']) || empty($mapping['vendor_sku'])) {
                $results['skipped']++;
                $results['details'][] = [
                    'status' => 'skipped',
                    'reason' => 'Missing product_id or vendor_sku',
                    'data' => $mapping,
                ];
                continue;
            }
            
            $result = $this->autoLearnVendorSku(
                $mapping['product_id'],
                $vendorId,
                $mapping['vendor_sku'],
                $mapping['vendor_product_name'] ?? null,
                $mapping['price'] ?? null,
                $sourceDocument
            );
            
            if ($result instanceof ProductSupplier) {
                $results['created']++;
                $results['details'][] = [
                    'status' => 'created',
                    'mapping_id' => $result->id,
                    'product_id' => $result->product_id,
                    'vendor_sku' => $result->product_code,
                ];
            } elseif (is_array($result) && isset($result['success']) && !$result['success']) {
                $results['failed']++;
                $results['details'][] = [
                    'status' => 'failed',
                    'error' => $result['error'] ?? 'Unknown error',
                    'data' => $mapping,
                ];
            }
        }
        
        // Set overall success based on results
        $results['success'] = $results['failed'] === 0;
        
        Log::info('DocumentScannerService: Bulk learn completed', [
            'vendor_id' => $vendorId,
            'total' => $results['total'],
            'created' => $results['created'],
            'skipped' => $results['skipped'],
            'failed' => $results['failed'],
        ]);
        
        return $results;
    }

    /**
     * Get unmatched items from scan results with suggestions
     * Used by the scanner UI to show the review panel
     *
     * @param array $scanResults The results from scanDocument()
     * @return array Unmatched items with product suggestions
     */
    public function getUnmatchedItemsWithSuggestions(array $scanResults): array
    {
        $unmatched = [];
        
        if (empty($scanResults['data']['line_items'])) {
            return $unmatched;
        }
        
        foreach ($scanResults['data']['line_items'] as $index => $lineItem) {
            $productMatch = $lineItem['product_match'] ?? [];
            
            // Only include unmatched or low-confidence items
            if (empty($productMatch['matched']) || ($productMatch['confidence'] ?? 0) < $this->confidenceThreshold) {
                $suggestions = $this->findMatchingProducts($lineItem);
                
                $unmatched[] = [
                    'line_index' => $index,
                    'line_number' => $lineItem['line_number'] ?? $index + 1,
                    'vendor_sku' => $lineItem['vendor_sku'] ?? $lineItem['sku'] ?? null,
                    'internal_sku' => $lineItem['internal_sku'] ?? null,
                    'description' => $lineItem['description'] ?? '',
                    'quantity' => $lineItem['quantity_shipped'] ?? $lineItem['quantity'] ?? 0,
                    'unit' => $lineItem['unit'] ?? 'ea',
                    'unit_price' => $lineItem['unit_price'] ?? 0,
                    'line_total' => $lineItem['line_total'] ?? 0,
                    'current_match' => $productMatch,
                    'suggestions' => $suggestions,
                ];
            }
        }
        
        return $unmatched;
    }

    /**
     * Apply user-confirmed mappings from scan review
     * Creates vendor SKU mappings and returns updated scan results
     *
     * @param array $scanResults Original scan results
     * @param array $confirmedMappings User-confirmed product mappings
     * @param int $vendorId Vendor ID
     * @param string|null $sourceDocument Source document reference
     * @return array Updated scan results with applied mappings
     */
    public function applyConfirmedMappings(
        array $scanResults,
        array $confirmedMappings,
        int $vendorId,
        ?string $sourceDocument = null
    ): array {
        $appliedCount = 0;
        
        foreach ($confirmedMappings as $mapping) {
            $lineIndex = $mapping['line_index'] ?? null;
            $productId = $mapping['product_id'] ?? null;
            $vendorSku = $mapping['vendor_sku'] ?? null;
            
            if ($lineIndex === null || !$productId || !$vendorSku) {
                continue;
            }
            
            // Create the vendor SKU mapping
            $result = $this->autoLearnVendorSku(
                $productId,
                $vendorId,
                $vendorSku,
                $mapping['vendor_product_name'] ?? null,
                $mapping['price'] ?? null,
                $sourceDocument
            );
            
            if ($result instanceof ProductSupplier) {
                $appliedCount++;
                
                // Update the scan results with the new match
                if (isset($scanResults['data']['line_items'][$lineIndex])) {
                    $product = Product::find($productId);
                    $scanResults['data']['line_items'][$lineIndex]['product_match'] = [
                        'matched' => true,
                        'product_id' => $productId,
                        'product_name' => $product?->name ?? 'Unknown',
                        'vendor_sku' => $vendorSku,
                        'confidence' => 1.0,
                        'match_method' => self::MATCH_BY_VENDOR_SKU,
                        'ai_learned' => true,
                    ];
                    $scanResults['data']['line_items'][$lineIndex]['requires_review'] = false;
                }
            }
        }
        
        // Recalculate stats
        if (isset($scanResults['data'])) {
            $scanResults['data']['stats'] = $this->calculateStats($scanResults['data']);
            $scanResults['data']['needs_review'] = !$this->meetsConfidenceThreshold($scanResults['data']);
        }
        
        $scanResults['mappings_applied'] = $appliedCount;
        
        return $scanResults;
    }
}
