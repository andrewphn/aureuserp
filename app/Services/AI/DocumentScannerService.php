<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
 * - Verification against existing POs and products
 * - Product matching via vendor SKUs
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

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Scan a document image and extract structured data
     *
     * @param UploadedFile|string $image The document image
     * @param string $documentType The type of document being scanned
     * @return array Extracted and verified data
     */
    public function scanDocument($image, string $documentType = self::TYPE_INVOICE): array
    {
        try {
            // Build the appropriate prompt based on document type
            $prompt = $this->buildExtractionPrompt($documentType);

            // Analyze the image with Gemini
            $response = $this->geminiService->analyzeImage($image, $prompt);

            // Parse the AI response
            $extractedData = $this->parseExtractionResponse($response, $documentType);

            if (!$extractedData['success']) {
                return $extractedData;
            }

            // Verify and enrich with database matches
            $verifiedData = $this->verifyAndEnrichData($extractedData['data'], $documentType);

            Log::info('DocumentScannerService: Document scanned successfully', [
                'document_type' => $documentType,
                'vendor_matched' => $verifiedData['vendor_match']['matched'] ?? false,
                'po_matched' => $verifiedData['po_match']['matched'] ?? false,
                'lines_count' => count($verifiedData['line_items'] ?? [])
            ]);

            return [
                'success' => true,
                'data' => $verifiedData,
                'document_type' => $documentType
            ];

        } catch (Exception $e) {
            Log::error('DocumentScannerService: Scan failed', [
                'document_type' => $documentType,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to scan document: ' . $e->getMessage(),
                'data' => null
            ];
        }
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

Extract the following information:
{
  "vendor": {
    "name": "Shipping vendor/supplier name"
  },
  "document": {
    "slip_number": "Packing slip or delivery number",
    "ship_date": "Ship date in YYYY-MM-DD format",
    "po_reference": "Purchase order reference",
    "tracking_number": "Tracking number if visible"
  },
  "line_items": [
    {
      "line_number": 1,
      "sku": "Vendor's product code/SKU",
      "description": "Product description",
      "quantity_shipped": 0.00,
      "quantity_ordered": 0.00,
      "quantity_backordered": 0.00,
      "unit": "Unit of measure"
    }
  ],
  "package_info": {
    "boxes": 0,
    "weight": "Total weight if shown",
    "carrier": "Shipping carrier name"
  },
  "confidence": 0.0 to 1.0
}

Be precise with quantities. Use null for fields you cannot read clearly.
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
                'data' => $data
            ];

        } catch (Exception $e) {
            Log::warning('DocumentScannerService: Failed to parse response', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500)
            ]);

            return [
                'success' => false,
                'error' => 'Could not parse document data: ' . $e->getMessage(),
                'data' => null
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
     * Match vendor name to existing partner
     */
    protected function matchVendor(string $vendorName): array
    {
        $vendorName = trim($vendorName);

        // Try exact match first
        $vendor = Partner::where('sub_type', 'supplier')
            ->where('name', $vendorName)
            ->first();

        // Try fuzzy match
        if (!$vendor) {
            $vendor = Partner::where('sub_type', 'supplier')
                ->where('name', 'like', '%' . $vendorName . '%')
                ->first();
        }

        // Try matching individual words for common vendor names
        if (!$vendor) {
            $words = explode(' ', $vendorName);
            foreach ($words as $word) {
                if (strlen($word) >= 4) { // Skip short words
                    $vendor = Partner::where('sub_type', 'supplier')
                        ->where('name', 'like', '%' . $word . '%')
                        ->first();
                    if ($vendor) break;
                }
            }
        }

        if ($vendor) {
            return [
                'matched' => true,
                'id' => $vendor->id,
                'name' => $vendor->name,
                'confidence' => $vendor->name === $vendorName ? 1.0 : 0.8
            ];
        }

        return [
            'matched' => false,
            'id' => null,
            'name' => $vendorName,
            'confidence' => 0,
            'suggestion' => 'Vendor not found. Create new vendor?'
        ];
    }

    /**
     * Match PO reference to existing purchase order
     */
    protected function matchPurchaseOrder(string $poRef, ?int $vendorId = null): array
    {
        $poRef = trim($poRef);

        // Build query
        $query = PurchaseOrder::query();

        // Try matching by PO name or partner reference
        $query->where(function ($q) use ($poRef) {
            $q->where('name', $poRef)
              ->orWhere('name', 'like', '%' . $poRef . '%')
              ->orWhere('partner_reference', $poRef)
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
                'lines_count' => $po->lines->count()
            ];
        }

        return [
            'matched' => false,
            'id' => null,
            'name' => $poRef,
            'order' => null,
            'suggestion' => 'PO not found. Check reference number.'
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

            // Try to match product by vendor SKU
            $sku = $line['sku'] ?? null;
            if ($sku && $vendorId) {
                $productMatch = $this->matchProductBySku($sku, $vendorId);
                $verifiedLine['product_match'] = $productMatch;
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
                        'status' => $this->getQuantityStatus($qtyShipped, $qtyRemaining)
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
                        'status' => $priceDiff < 0.01 ? 'match' : ($priceDiff < $poPrice * 0.05 ? 'close' : 'mismatch')
                    ];
                }
            }

            $verifiedLines[] = $verifiedLine;
        }

        return $verifiedLines;
    }

    /**
     * Match product by vendor's SKU/product code
     */
    protected function matchProductBySku(string $sku, int $vendorId): array
    {
        $sku = trim($sku);

        // Look up in product_suppliers table
        $productSupplier = ProductSupplier::where('partner_id', $vendorId)
            ->where(function ($q) use ($sku) {
                $q->where('product_code', $sku)
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
                'confidence' => $productSupplier->product_code === $sku ? 1.0 : 0.8
            ];
        }

        // Try matching by internal product SKU
        $product = Product::where('default_code', $sku)
            ->orWhere('barcode', $sku)
            ->first();

        if ($product) {
            return [
                'matched' => true,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'vendor_sku' => null,
                'confidence' => 0.7,
                'note' => 'Matched by internal SKU, not vendor code'
            ];
        }

        return [
            'matched' => false,
            'product_id' => null,
            'searched_sku' => $sku,
            'suggestion' => 'Product not found. Add vendor product code?'
        ];
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
                'uom' => $poLine->uom?->name
            ];
        }

        return [
            'matched' => false,
            'product_id' => $productId,
            'suggestion' => 'Product not on this PO'
        ];
    }

    /**
     * Get quantity status for receiving verification
     */
    protected function getQuantityStatus(float $shipped, float $remaining): string
    {
        if ($shipped == $remaining) {
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
                'status' => abs($invoiceSubtotal - $poSubtotal) < 0.02 ? 'match' : 'mismatch'
            ],
            'tax' => [
                'invoice' => $invoiceTax,
                'po' => $poTax,
                'difference' => abs($invoiceTax - $poTax),
                'status' => abs($invoiceTax - $poTax) < 0.02 ? 'match' : 'mismatch'
            ],
            'total' => [
                'invoice' => $invoiceTotal,
                'po' => $poTotal,
                'difference' => abs($invoiceTotal - $poTotal),
                'status' => abs($invoiceTotal - $poTotal) < 0.02 ? 'match' : 'mismatch'
            ]
        ];
    }
}
