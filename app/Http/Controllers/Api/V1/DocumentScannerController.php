<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\AI\DocumentScannerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * API Controller for AI-powered document scanning
 *
 * Provides endpoints for scanning invoices, packing slips, and product labels
 * to extract data and verify against existing POs and products.
 */
class DocumentScannerController extends BaseApiController
{
    protected DocumentScannerService $scannerService;

    public function __construct(DocumentScannerService $scannerService)
    {
        $this->scannerService = $scannerService;
    }

    /**
     * Scan a document image and extract data
     *
     * POST /api/v1/documents/scan
     *
     * Request (multipart/form-data):
     * - document: (file) Image file (JPEG, PNG, PDF)
     * - type: (string) Document type: invoice, packing_slip, quote, product_label
     *
     * Alternative Request (JSON):
     * - document_base64: (string) Base64-encoded image data
     * - type: (string) Document type
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "vendor": { "name": "...", "address": "..." },
     *     "vendor_match": { "matched": true, "id": 123, "name": "..." },
     *     "document": { "invoice_number": "...", "po_reference": "..." },
     *     "po_match": { "matched": true, "id": 456, "name": "PO-001" },
     *     "line_items": [
     *       {
     *         "sku": "ABC123",
     *         "description": "Product Name",
     *         "quantity": 10,
     *         "unit_price": 25.00,
     *         "product_match": { "matched": true, "product_id": 789 },
     *         "verification": { "quantity": {...}, "price": {...} }
     *       }
     *     ],
     *     "totals": { "subtotal": 250.00, "tax": 20.00, "total": 270.00 },
     *     "confidence": 0.95
     *   }
     * }
     */
    public function scan(Request $request): JsonResponse
    {
        // Validate request - accept either file upload or base64
        $validator = Validator::make($request->all(), [
            'document' => 'required_without:document_base64|file|mimes:jpeg,jpg,png,pdf,webp|max:10240',
            'document_base64' => 'required_without:document|string',
            'type' => 'required|string|in:invoice,packing_slip,quote,product_label',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $documentType = $request->input('type');

        $this->logActivity('document_scan', [
            'type' => $documentType,
            'has_file' => $request->hasFile('document'),
            'has_base64' => $request->has('document_base64'),
        ]);

        try {
            // Get the image data
            if ($request->hasFile('document')) {
                $image = $request->file('document');
            } else {
                $image = $request->input('document_base64');
            }

            // Scan the document
            $result = $this->scannerService->scanDocument($image, $documentType);

            if (!$result['success']) {
                return $this->error(
                    $result['error'] ?? 'Could not extract document data',
                    null,
                    422
                );
            }

            // Clean up response for API (remove Eloquent models)
            $responseData = $this->cleanResponseData($result['data']);

            return $this->success(
                $responseData,
                'Document scanned successfully'
            );

        } catch (\Exception $e) {
            return $this->error('Document scan failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Quick scan for product label/barcode
     *
     * POST /api/v1/documents/scan-product
     *
     * This is a simpler endpoint optimized for quick product lookups
     * from barcode scans or product labels.
     */
    public function scanProduct(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required_without:document_base64|file|mimes:jpeg,jpg,png,webp|max:5120',
            'document_base64' => 'required_without:document|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $image = $request->hasFile('document')
                ? $request->file('document')
                : $request->input('document_base64');

            $result = $this->scannerService->scanDocument(
                $image,
                DocumentScannerService::TYPE_PRODUCT_LABEL
            );

            if (!$result['success']) {
                return $this->error(
                    $result['error'] ?? 'Could not read product information',
                    null,
                    422
                );
            }

            return $this->success(
                $this->cleanResponseData($result['data']),
                'Product scanned successfully'
            );

        } catch (\Exception $e) {
            return $this->error('Product scan failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Scan for receiving - optimized for packing slips
     *
     * POST /api/v1/documents/scan-receiving
     *
     * Specialized endpoint for receiving operations that:
     * - Extracts packing slip data
     * - Matches to existing PO
     * - Verifies quantities against PO lines
     * - Returns data ready for receiving form population
     */
    public function scanReceiving(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required_without:document_base64|file|mimes:jpeg,jpg,png,pdf,webp|max:10240',
            'document_base64' => 'required_without:document|string',
            'po_id' => 'nullable|integer|exists:purchases_orders,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $this->logActivity('document_scan_receiving', [
            'has_file' => $request->hasFile('document'),
            'po_id' => $request->input('po_id'),
        ]);

        try {
            $image = $request->hasFile('document')
                ? $request->file('document')
                : $request->input('document_base64');

            $result = $this->scannerService->scanDocument(
                $image,
                DocumentScannerService::TYPE_PACKING_SLIP
            );

            if (!$result['success']) {
                return $this->error(
                    $result['error'] ?? 'Could not read packing slip',
                    null,
                    422
                );
            }

            // Format response for receiving form
            $responseData = $this->formatReceivingResponse($result['data']);

            return $this->success(
                $responseData,
                'Packing slip scanned successfully'
            );

        } catch (\Exception $e) {
            return $this->error('Receiving scan failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get supported document types
     *
     * GET /api/v1/documents/types
     */
    public function getDocumentTypes(): JsonResponse
    {
        return $this->success([
            [
                'type' => DocumentScannerService::TYPE_INVOICE,
                'label' => 'Invoice / Bill',
                'description' => 'Vendor invoices and bills for payment processing',
            ],
            [
                'type' => DocumentScannerService::TYPE_PACKING_SLIP,
                'label' => 'Packing Slip',
                'description' => 'Shipping documents for receiving operations',
            ],
            [
                'type' => DocumentScannerService::TYPE_QUOTE,
                'label' => 'Quote / Estimate',
                'description' => 'Vendor quotes for price comparison',
            ],
            [
                'type' => DocumentScannerService::TYPE_PRODUCT_LABEL,
                'label' => 'Product Label',
                'description' => 'Product labels and barcodes for quick lookup',
            ],
        ], 'Document types retrieved');
    }

    /**
     * Clean response data by removing Eloquent models and sensitive data
     */
    protected function cleanResponseData(array $data): array
    {
        // Remove full Eloquent models from po_match
        if (isset($data['po_match']['order'])) {
            unset($data['po_match']['order']);
        }

        // Clean up line items
        if (isset($data['line_items'])) {
            foreach ($data['line_items'] as &$line) {
                if (isset($line['po_line_match']['po_line'])) {
                    unset($line['po_line_match']['po_line']);
                }
            }
        }

        return $data;
    }

    /**
     * Format response data specifically for receiving form population
     */
    protected function formatReceivingResponse(array $data): array
    {
        $response = [
            'vendor' => $data['vendor'] ?? null,
            'vendor_id' => $data['vendor_match']['id'] ?? null,
            'vendor_matched' => $data['vendor_match']['matched'] ?? false,
            'po_reference' => $data['document']['po_reference'] ?? null,
            'po_id' => $data['po_match']['id'] ?? null,
            'po_matched' => $data['po_match']['matched'] ?? false,
            'slip_number' => $data['document']['slip_number'] ?? null,
            'ship_date' => $data['document']['ship_date'] ?? null,
            'tracking_number' => $data['document']['tracking_number'] ?? null,
            'package_info' => $data['package_info'] ?? null,
            'lines' => [],
            'confidence' => $data['confidence'] ?? 0,
        ];

        // Format line items for receiving form
        if (!empty($data['line_items'])) {
            foreach ($data['line_items'] as $line) {
                $formattedLine = [
                    'sku' => $line['sku'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity_shipped' => $line['quantity_shipped'] ?? $line['quantity'] ?? 0,
                    'unit' => $line['unit'] ?? null,
                    'product_id' => $line['product_match']['product_id'] ?? null,
                    'product_name' => $line['product_match']['product_name'] ?? null,
                    'product_matched' => $line['product_match']['matched'] ?? false,
                    'po_line_id' => $line['po_line_match']['po_line_id'] ?? null,
                    'po_line_matched' => $line['po_line_match']['matched'] ?? false,
                ];

                // Add verification data if available
                if (!empty($line['verification']['quantity'])) {
                    $formattedLine['verification'] = $line['verification']['quantity'];
                }

                $response['lines'][] = $formattedLine;
            }
        }

        // Add summary
        $response['summary'] = [
            'total_lines' => count($response['lines']),
            'matched_products' => count(array_filter($response['lines'], fn($l) => $l['product_matched'])),
            'unmatched_products' => count(array_filter($response['lines'], fn($l) => !$l['product_matched'])),
        ];

        return $response;
    }
}
