<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\DocumentScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Models\Product;

class DocumentScannerApiController extends Controller
{
    protected DocumentScannerService $scannerService;

    public function __construct(DocumentScannerService $scannerService)
    {
        $this->scannerService = $scannerService;
    }

    /**
     * Scan a document (receiving/packing slip)
     */
    public function scanReceiving(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'type' => 'string|in:invoice,packing_slip,quote,product_label',
        ]);

        $document = $request->file('document');
        $type = $request->input('type', DocumentScannerService::TYPE_PACKING_SLIP);

        $result = $this->scannerService->scanDocument($document, $type);

        return response()->json($result);
    }

    /**
     * Learn vendor SKU mappings from confirmed selections
     */
    public function learnMappings(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_id' => 'required|integer|exists:partners_partners,id',
            'mappings' => 'required|array',
            'mappings.*.product_id' => 'required|integer|exists:products_products,id',
            'mappings.*.vendor_sku' => 'required|string|max:255',
            'mappings.*.vendor_product_name' => 'nullable|string|max:255',
            'mappings.*.price' => 'nullable|numeric|min:0',
            'source_document' => 'nullable|string|max:255',
        ]);

        $vendorId = $request->input('vendor_id');
        $mappings = $request->input('mappings');
        $sourceDocument = $request->input('source_document');

        $result = $this->scannerService->bulkLearnVendorSkus($mappings, $vendorId, $sourceDocument);

        return response()->json($result);
    }

    /**
     * Create a new product from scan data
     */
    public function createProduct(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'thickness' => 'nullable|string|max:50',
            'sheet_size' => 'nullable|string|max:50',
            'sqft_per_sheet' => 'nullable|numeric|min:0',
            'vendor_id' => 'nullable|integer|exists:partners_partners,id',
            'vendor_sku' => 'nullable|string|max:255',
        ]);

        try {
            // Parse thickness fraction to decimal
            $thicknessInches = null;
            $thickness = $request->input('thickness');
            if ($thickness && preg_match('/(\d+)\/(\d+)/', $thickness, $matches)) {
                $thicknessInches = round(intval($matches[1]) / intval($matches[2]), 3);
            }

            // Create the product
            $product = Product::create([
                'name' => $request->input('name'),
                'reference' => $request->input('reference'),
                'type' => 'product',
                'tracking' => 'none',
                'is_storable' => true,
                'purchase_ok' => true,
                'sales_ok' => true,
                'thickness_inches' => $thicknessInches,
                'sheet_size' => $request->input('sheet_size'),
                'sqft_per_sheet' => $request->input('sqft_per_sheet'),
                'creator_id' => Auth::id() ?? 1,
                'company_id' => 1,
            ]);

            // If vendor info provided, create vendor SKU mapping
            $vendorId = $request->input('vendor_id');
            $vendorSku = $request->input('vendor_sku');

            if ($vendorId && $vendorSku) {
                $this->scannerService->autoLearnVendorSku(
                    $product->id,
                    $vendorId,
                    $vendorSku,
                    $request->input('name'),
                    null,
                    'product-create'
                );
            }

            Log::info('DocumentScannerApiController: Created product', [
                'product_id' => $product->id,
                'name' => $product->name,
                'vendor_sku' => $vendorSku,
            ]);

            return response()->json([
                'success' => true,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'message' => "Product '{$product->name}' created successfully.",
            ]);

        } catch (\Exception $e) {
            Log::error('DocumentScannerApiController: Failed to create product', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create product: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * List products for dropdown
     */
    public function listProducts(Request $request): JsonResponse
    {
        $limit = min($request->input('limit', 100), 500);
        $search = $request->input('search');

        $query = Product::query()
            ->select('id', 'name', 'reference', 'thickness_inches', 'sheet_size', 'sqft_per_sheet')
            ->where('purchase_ok', true)
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $products = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'products' => $products,
            'count' => $products->count(),
        ]);
    }
}
