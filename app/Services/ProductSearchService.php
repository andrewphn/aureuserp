<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Product\Models\Product;
use Webkul\Project\Models\MaterialReservation;
use Webkul\Project\Models\Project;

/**
 * Product Search Service for AI Assistant
 *
 * Provides product search, availability checking, and reservation capabilities
 * for the Cabinet AI Assistant to use when helping users find and reserve products.
 */
class ProductSearchService
{
    /**
     * Search products by query string
     *
     * @param string $query Search term
     * @param array $options Optional filters (category, product_type, limit)
     * @return array Search results with product details
     */
    public function searchProducts(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $category = $options['category'] ?? null;
        $productType = $options['product_type'] ?? null;

        try {
            $productsQuery = Product::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('reference', 'LIKE', "%{$query}%")
                        ->orWhere('barcode', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%")
                        // Also search by supplier product code
                        ->orWhereHas('supplierInformation', function ($sq) use ($query) {
                            $sq->where('product_code', 'LIKE', "%{$query}%")
                                ->orWhere('product_name', 'LIKE', "%{$query}%");
                        });
                })
                ->with(['category', 'uom', 'supplierInformation']);

            // Apply category filter
            if ($category) {
                $productsQuery->whereHas('category', function ($q) use ($category) {
                    $q->where('name', 'LIKE', "%{$category}%");
                });
            }

            // Apply product type filter
            if ($productType) {
                $productsQuery->where('type', strtoupper($productType));
            }

            $products = $productsQuery->limit($limit)->get();

            // Get inventory quantities for all found products
            $productIds = $products->pluck('id')->toArray();
            $quantities = $this->getQuantitiesForProducts($productIds);

            return [
                'success' => true,
                'count' => $products->count(),
                'products' => $products->map(function ($product) use ($quantities) {
                    $qty = $quantities->get($product->id);
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'reference' => $product->reference,
                        'barcode' => $product->barcode,
                        'price' => $product->price,
                        'cost' => $product->cost,
                        'type' => $product->type,
                        'category' => $product->category?->name,
                        'uom' => $product->uom?->name ?? 'unit',
                        'available_qty' => $qty['available'] ?? 0,
                        'reserved_qty' => $qty['reserved'] ?? 0,
                        'total_qty' => $qty['total'] ?? 0,
                        'supplier_sku' => $product->supplierInformation?->first()?->product_code,
                    ];
                })->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('ProductSearchService: Search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Failed to search products: ' . $e->getMessage(),
                'products' => [],
            ];
        }
    }

    /**
     * Get product details by ID or name
     */
    public function getProductDetails(?int $productId = null, ?string $productName = null): array
    {
        try {
            $product = null;

            if ($productId) {
                $product = Product::with(['category', 'uom', 'supplierInformation'])->find($productId);
            } elseif ($productName) {
                $product = Product::with(['category', 'uom', 'supplierInformation'])
                    ->where('name', 'LIKE', "%{$productName}%")
                    ->orWhere('reference', $productName)
                    ->first();
            }

            if (!$product) {
                return [
                    'success' => false,
                    'error' => 'Product not found',
                ];
            }

            $quantities = $this->getQuantitiesForProducts([$product->id]);
            $qty = $quantities->get($product->id, ['available' => 0, 'reserved' => 0, 'total' => 0]);

            return [
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'reference' => $product->reference,
                    'barcode' => $product->barcode,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'type' => $product->type,
                    'category' => $product->category?->name,
                    'uom' => $product->uom?->name ?? 'unit',
                    'description' => $product->description,
                    'available_qty' => $qty['available'],
                    'reserved_qty' => $qty['reserved'],
                    'total_qty' => $qty['total'],
                    'supplier_sku' => $product->supplierInformation?->first()?->product_code,
                    'suppliers' => $product->supplierInformation?->map(fn($s) => [
                        'name' => $s->partner?->name,
                        'sku' => $s->product_code,
                        'price' => $s->price,
                    ])->toArray() ?? [],
                ],
            ];
        } catch (\Exception $e) {
            Log::error('ProductSearchService: Get details failed', [
                'productId' => $productId,
                'productName' => $productName,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Failed to get product details: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check product availability
     */
    public function checkAvailability(?int $productId = null, ?string $productName = null, float $quantityNeeded = 1): array
    {
        try {
            // Find product
            $product = null;
            if ($productId) {
                $product = Product::find($productId);
            } elseif ($productName) {
                $product = Product::where('name', 'LIKE', "%{$productName}%")
                    ->orWhere('reference', $productName)
                    ->first();
            }

            if (!$product) {
                return [
                    'success' => false,
                    'error' => 'Product not found',
                    'available' => false,
                ];
            }

            $quantities = $this->getQuantitiesForProducts([$product->id]);
            $qty = $quantities->get($product->id, ['available' => 0, 'reserved' => 0, 'total' => 0]);

            $available = $qty['available'] >= $quantityNeeded;

            return [
                'success' => true,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'available' => $available,
                'quantity_needed' => $quantityNeeded,
                'quantity_available' => $qty['available'],
                'quantity_reserved' => $qty['reserved'],
                'quantity_total' => $qty['total'],
                'shortage' => $available ? 0 : ($quantityNeeded - $qty['available']),
            ];
        } catch (\Exception $e) {
            Log::error('ProductSearchService: Availability check failed', [
                'productId' => $productId,
                'productName' => $productName,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Failed to check availability: ' . $e->getMessage(),
                'available' => false,
            ];
        }
    }

    /**
     * Reserve a product for a project
     */
    public function reserveProduct(
        int $projectId,
        ?int $productId = null,
        ?string $productName = null,
        float $quantity = 1,
        ?string $notes = null
    ): array {
        try {
            // Find product
            $product = null;
            if ($productId) {
                $product = Product::find($productId);
            } elseif ($productName) {
                $product = Product::where('name', 'LIKE', "%{$productName}%")
                    ->orWhere('reference', $productName)
                    ->first();
            }

            if (!$product) {
                return [
                    'success' => false,
                    'error' => 'Product not found. Please search for the product first.',
                ];
            }

            // Get project and warehouse
            $project = Project::find($projectId);
            if (!$project) {
                return [
                    'success' => false,
                    'error' => 'Project not found.',
                ];
            }

            $warehouseId = $project->warehouse_id;
            if (!$warehouseId) {
                // Get default warehouse
                $warehouse = Warehouse::first();
                $warehouseId = $warehouse?->id;
            }

            if (!$warehouseId) {
                return [
                    'success' => false,
                    'error' => 'No warehouse configured. Please assign a warehouse to the project.',
                ];
            }

            $warehouse = Warehouse::find($warehouseId);

            // Check availability
            $availabilityCheck = $this->checkAvailability($product->id, null, $quantity);
            if (!$availabilityCheck['available']) {
                return [
                    'success' => false,
                    'error' => "Insufficient inventory. Available: {$availabilityCheck['quantity_available']}, Needed: {$quantity}",
                    'available_qty' => $availabilityCheck['quantity_available'],
                    'shortage' => $availabilityCheck['shortage'],
                ];
            }

            // Create reservation
            $reservation = MaterialReservation::create([
                'project_id' => $project->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'location_id' => $warehouse->lot_stock_location_id,
                'quantity_reserved' => $quantity,
                'unit_of_measure' => $product->uom?->name ?? 'unit',
                'status' => MaterialReservation::STATUS_RESERVED,
                'reserved_by' => auth()->id(),
                'reserved_at' => now(),
                'expires_at' => now()->addDays(30),
                'notes' => $notes ?? "Reserved via AI Assistant",
            ]);

            // Update reserved quantity
            ProductQuantity::where('product_id', $product->id)
                ->where('location_id', $warehouse->lot_stock_location_id)
                ->increment('reserved_quantity', $quantity);

            Log::info('ProductSearchService: Product reserved via AI', [
                'project_id' => $project->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'reservation_id' => $reservation->id,
            ]);

            return [
                'success' => true,
                'message' => "Reserved {$quantity} {$product->uom?->name} of {$product->name} for project {$project->project_number}",
                'reservation' => [
                    'id' => $reservation->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'project' => $project->project_number,
                    'expires_at' => $reservation->expires_at->format('Y-m-d'),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('ProductSearchService: Reservation failed', [
                'projectId' => $projectId,
                'productId' => $productId,
                'productName' => $productName,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Failed to reserve product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get inventory quantities for multiple products
     */
    protected function getQuantitiesForProducts(array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        // Get all quantities across all stock locations
        $quantities = ProductQuantity::whereIn('product_id', $productIds)
            ->whereHas('location', function ($q) {
                $q->where('type', LocationType::INTERNAL); // Only internal stock locations
            })
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $total = $items->sum('quantity');
                $reserved = $items->sum('reserved_quantity');
                return [
                    'total' => $total,
                    'reserved' => $reserved,
                    'available' => max(0, $total - $reserved),
                ];
            });

        return $quantities;
    }
}
