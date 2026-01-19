<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Inventory\Models\ProductQuantity;

/**
 * Stock Controller for V1 API
 *
 * Handles inventory stock levels (ProductQuantity).
 * Provides read access to stock quantities and supports
 * inventory adjustments through manual updates.
 *
 * Note: For full inventory moves, use the MoveController.
 */
class StockController extends BaseResourceController
{
    protected string $modelClass = ProductQuantity::class;

    protected array $searchableFields = [];

    protected array $filterableFields = [
        'id',
        'product_id',
        'location_id',
        'warehouse_id',
        'lot_id',
        'package_id',
        'company_id',
    ];

    protected array $sortableFields = [
        'id',
        'quantity',
        'reserved_quantity',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'product',
        'location',
        'warehouse',
        'lot',
        'package',
        'company',
    ];

    protected function validateStore(): array
    {
        return [
            'product_id' => 'required|integer|exists:products_products,id',
            'location_id' => 'required|integer|exists:inventories_locations,id',
            'warehouse_id' => 'nullable|integer|exists:inventories_warehouses,id',
            'lot_id' => 'nullable|integer|exists:inventories_lots,id',
            'package_id' => 'nullable|integer|exists:inventories_packages,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'quantity' => 'required|numeric',
            'reserved_quantity' => 'nullable|numeric|min:0',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'quantity' => 'sometimes|numeric',
            'reserved_quantity' => 'nullable|numeric|min:0',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        return $data;
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['available_quantity'] = ($model->quantity ?? 0) - ($model->reserved_quantity ?? 0);
        $data['is_available'] = $data['available_quantity'] > 0;

        return $data;
    }

    /**
     * GET /stock/by-product/{productId} - Get stock for a product across all locations
     */
    public function byProduct(int $productId): JsonResponse
    {
        $stocks = ProductQuantity::with(['location', 'warehouse', 'lot'])
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->get();

        $totalQuantity = $stocks->sum('quantity');
        $totalReserved = $stocks->sum('reserved_quantity');
        $totalAvailable = $totalQuantity - $totalReserved;

        return $this->success([
            'product_id' => $productId,
            'total_quantity' => $totalQuantity,
            'total_reserved' => $totalReserved,
            'total_available' => $totalAvailable,
            'locations' => $stocks->map(fn($s) => $this->transformModel($s)),
        ], 'Stock by product retrieved');
    }

    /**
     * GET /stock/by-location/{locationId} - Get all stock in a location
     */
    public function byLocation(int $locationId): JsonResponse
    {
        $stocks = ProductQuantity::with(['product', 'lot', 'package'])
            ->where('location_id', $locationId)
            ->where('quantity', '>', 0)
            ->get();

        return $this->success([
            'location_id' => $locationId,
            'total_products' => $stocks->count(),
            'total_quantity' => $stocks->sum('quantity'),
            'items' => $stocks->map(fn($s) => $this->transformModel($s)),
        ], 'Stock by location retrieved');
    }

    /**
     * GET /stock/by-warehouse/{warehouseId} - Get all stock in a warehouse
     */
    public function byWarehouse(int $warehouseId): JsonResponse
    {
        $stocks = ProductQuantity::with(['product', 'location', 'lot'])
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0)
            ->get();

        // Group by product
        $grouped = $stocks->groupBy('product_id')->map(function ($items) {
            return [
                'product_id' => $items->first()->product_id,
                'product_name' => $items->first()->product?->name,
                'total_quantity' => $items->sum('quantity'),
                'total_reserved' => $items->sum('reserved_quantity'),
                'total_available' => $items->sum('quantity') - $items->sum('reserved_quantity'),
                'locations' => $items->map(fn($s) => [
                    'location_id' => $s->location_id,
                    'location_name' => $s->location?->complete_name,
                    'quantity' => $s->quantity,
                    'reserved' => $s->reserved_quantity,
                ]),
            ];
        });

        return $this->success([
            'warehouse_id' => $warehouseId,
            'total_products' => $grouped->count(),
            'total_quantity' => $stocks->sum('quantity'),
            'products' => $grouped->values(),
        ], 'Stock by warehouse retrieved');
    }

    /**
     * POST /stock/adjust - Adjust stock quantity (inventory adjustment)
     */
    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products_products,id',
            'location_id' => 'required|integer|exists:inventories_locations,id',
            'quantity' => 'required|numeric',
            'lot_id' => 'nullable|integer|exists:inventories_lots,id',
            'reason' => 'nullable|string|max:255',
        ]);

        // Find existing stock record
        $stock = ProductQuantity::where('product_id', $validated['product_id'])
            ->where('location_id', $validated['location_id'])
            ->when($validated['lot_id'] ?? null, fn($q, $lotId) => $q->where('lot_id', $lotId))
            ->first();

        $previousQty = $stock?->quantity ?? 0;
        $newQty = $validated['quantity'];

        if ($stock) {
            $stock->update(['quantity' => $newQty]);
        } else {
            $stock = ProductQuantity::create([
                'product_id' => $validated['product_id'],
                'location_id' => $validated['location_id'],
                'lot_id' => $validated['lot_id'] ?? null,
                'quantity' => $newQty,
                'creator_id' => $request->user()->id,
            ]);
        }

        // Log the adjustment
        \Log::info('Stock adjustment', [
            'product_id' => $validated['product_id'],
            'location_id' => $validated['location_id'],
            'previous_qty' => $previousQty,
            'new_qty' => $newQty,
            'adjustment' => $newQty - $previousQty,
            'reason' => $validated['reason'] ?? null,
            'user_id' => $request->user()->id,
        ]);

        $this->dispatchWebhookEvent($stock, 'adjusted');

        return $this->success([
            'stock' => $this->transformModel($stock->fresh()),
            'adjustment' => [
                'previous_quantity' => $previousQty,
                'new_quantity' => $newQty,
                'difference' => $newQty - $previousQty,
            ],
        ], 'Stock adjusted successfully');
    }

    /**
     * GET /stock/availability - Check availability for multiple products
     */
    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products_products,id',
            'products.*.quantity_needed' => 'required|numeric|min:0',
            'warehouse_id' => 'nullable|integer|exists:inventories_warehouses,id',
            'location_id' => 'nullable|integer|exists:inventories_locations,id',
        ]);

        $results = [];
        $allAvailable = true;

        foreach ($validated['products'] as $item) {
            $query = ProductQuantity::where('product_id', $item['product_id']);

            if ($validated['warehouse_id'] ?? null) {
                $query->where('warehouse_id', $validated['warehouse_id']);
            }
            if ($validated['location_id'] ?? null) {
                $query->where('location_id', $validated['location_id']);
            }

            $totalQty = $query->sum('quantity');
            $totalReserved = $query->sum('reserved_quantity');
            $available = $totalQty - $totalReserved;

            $isAvailable = $available >= $item['quantity_needed'];
            if (!$isAvailable) {
                $allAvailable = false;
            }

            $results[] = [
                'product_id' => $item['product_id'],
                'quantity_needed' => $item['quantity_needed'],
                'quantity_available' => $available,
                'quantity_total' => $totalQty,
                'quantity_reserved' => $totalReserved,
                'is_available' => $isAvailable,
                'shortfall' => $isAvailable ? 0 : $item['quantity_needed'] - $available,
            ];
        }

        return $this->success([
            'all_available' => $allAvailable,
            'products' => $results,
        ], $allAvailable ? 'All products available' : 'Some products unavailable');
    }
}
