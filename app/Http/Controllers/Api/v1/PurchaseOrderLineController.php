<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Purchase\Models\OrderLine;
use Webkul\Purchase\Models\Order;

/**
 * Purchase Order Line Controller for V1 API
 *
 * Handles line items on purchase orders/RFQs.
 * Can be accessed as nested resource or shallow routes.
 *
 * Supports project integration:
 * - project_id: Links to which project this purchase is for
 * - bom_id: Links to cabinet materials BOM entry
 * - hardware_requirement_id: Links to hardware requirement
 */
class PurchaseOrderLineController extends BaseResourceController
{
    protected string $modelClass = OrderLine::class;

    protected array $searchableFields = [
        'name',
        'product_description_variants',
    ];

    protected array $filterableFields = [
        'id',
        'order_id',
        'product_id',
        'state',
        'company_id',
        'currency_id',
        'partner_id',
        'project_id',
        'bom_id',
        'hardware_requirement_id',
        'is_downpayment',
    ];

    protected array $sortableFields = [
        'id',
        'sort',
        'product_qty',
        'price_unit',
        'price_subtotal',
        'price_total',
        'qty_received',
        'qty_invoiced',
        'planned_at',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'order',
        'order.partner',
        'product',
        'uom',
        'taxes',
        'company',
        'currency',
        'partner',
        'creator',
        'inventoryMoves',
        'accountMoveLines',
        'project',
        'bom',
        'hardwareRequirement',
        'finalLocation',
    ];

    /**
     * Override index to support nested routing
     */
    public function index(Request $request, ?int $orderId = null): JsonResponse
    {
        if ($orderId !== null) {
            $order = Order::find($orderId);
            if (!$order) {
                return $this->notFound('Purchase order not found');
            }

            // Force filter by order_id
            $request->merge(['order_id' => $orderId]);
        }

        return parent::index($request);
    }

    /**
     * Override store to support nested routing
     */
    public function store(Request $request, ?int $orderId = null): JsonResponse
    {
        if ($orderId !== null) {
            $order = Order::find($orderId);
            if (!$order) {
                return $this->notFound('Purchase order not found');
            }

            // Inject order context
            $request->merge([
                'order_id' => $orderId,
                'company_id' => $order->company_id,
                'currency_id' => $order->currency_id,
                'partner_id' => $order->partner_id,
                'state' => $order->state,
            ]);
        }

        return parent::store($request);
    }

    protected function validateStore(): array
    {
        return [
            'order_id' => 'required|integer|exists:purchases_orders,id',
            'product_id' => 'required|integer|exists:products_products,id',
            'uom_id' => 'nullable|integer|exists:uoms,id',
            'product_qty' => 'required|numeric|min:0',
            'price_unit' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'name' => 'nullable|string',
            'display_type' => 'nullable|string|in:line_section,line_note',
            'planned_at' => 'nullable|date',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'bom_id' => 'nullable|integer|exists:projects_cabinet_materials_bom,id',
            'hardware_requirement_id' => 'nullable|integer|exists:projects_hardware_requirements,id',
            'final_location_id' => 'nullable|integer|exists:inventories_locations,id',
            'tax_ids' => 'nullable|array',
            'tax_ids.*' => 'integer|exists:accounts_taxes,id',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'product_id' => 'sometimes|integer|exists:products_products,id',
            'uom_id' => 'nullable|integer|exists:uoms,id',
            'product_qty' => 'sometimes|numeric|min:0',
            'price_unit' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'name' => 'nullable|string',
            'display_type' => 'nullable|string|in:line_section,line_note',
            'planned_at' => 'nullable|date',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'bom_id' => 'nullable|integer|exists:projects_cabinet_materials_bom,id',
            'hardware_requirement_id' => 'nullable|integer|exists:projects_hardware_requirements,id',
            'final_location_id' => 'nullable|integer|exists:inventories_locations,id',
            'qty_received_manual' => 'nullable|numeric|min:0',
            'tax_ids' => 'nullable|array',
            'tax_ids.*' => 'integer|exists:accounts_taxes,id',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        // Auto-populate from product if not set
        if (isset($data['product_id']) && !isset($data['price_unit'])) {
            $product = \Webkul\Product\Models\Product::find($data['product_id']);
            if ($product) {
                $data['price_unit'] = $product->cost_price ?? $product->list_price ?? 0;

                if (!isset($data['name'])) {
                    $data['name'] = $product->name;
                }

                if (!isset($data['uom_id'])) {
                    $data['uom_id'] = $product->uom_id ?? $product->purchase_uom_id;
                }
            }
        }

        // Calculate subtotals
        $qty = $data['product_qty'] ?? 0;
        $price = $data['price_unit'] ?? 0;
        $discount = $data['discount'] ?? 0;

        $data['price_subtotal'] = $qty * $price * (1 - $discount / 100);
        $data['price_total'] = $data['price_subtotal']; // Tax calculated separately

        return $data;
    }

    protected function afterStore(Model $model, Request $request): void
    {
        // Sync taxes if provided
        if ($request->has('tax_ids')) {
            $model->taxes()->sync($request->input('tax_ids'));
            $this->recalculateTax($model);
        }

        // Recalculate order totals
        $this->recalculateOrderTotals($model->order_id);
    }

    protected function beforeUpdate(array $data, Model $model, Request $request): array
    {
        // Recalculate subtotals if relevant fields changed
        $qty = $data['product_qty'] ?? $model->product_qty ?? 0;
        $price = $data['price_unit'] ?? $model->price_unit ?? 0;
        $discount = $data['discount'] ?? $model->discount ?? 0;

        $data['price_subtotal'] = $qty * $price * (1 - $discount / 100);
        $data['price_total'] = $data['price_subtotal'] + ($model->price_tax ?? 0);

        return $data;
    }

    protected function afterUpdate(Model $model, Request $request): void
    {
        // Sync taxes if provided
        if ($request->has('tax_ids')) {
            $model->taxes()->sync($request->input('tax_ids'));
            $this->recalculateTax($model);
        }

        // Recalculate order totals
        $this->recalculateOrderTotals($model->order_id);
    }

    protected function afterDestroy(Model $model, Request $request): void
    {
        // Recalculate order totals after delete
        $this->recalculateOrderTotals($model->order_id);
    }

    /**
     * Recalculate tax for a line based on attached taxes
     */
    protected function recalculateTax(OrderLine $line): void
    {
        $line->load('taxes');

        $taxAmount = 0;
        foreach ($line->taxes as $tax) {
            if ($tax->amount_type === 'percent') {
                $taxAmount += $line->price_subtotal * ($tax->amount / 100);
            } else {
                $taxAmount += $tax->amount * ($line->product_qty ?? 1);
            }
        }

        $line->updateQuietly([
            'price_tax' => $taxAmount,
            'price_total' => $line->price_subtotal + $taxAmount,
        ]);
    }

    /**
     * Recalculate order totals based on lines
     */
    protected function recalculateOrderTotals(int $orderId): void
    {
        $order = Order::with('lines')->find($orderId);

        if (!$order) {
            return;
        }

        $untaxed = $order->lines->sum('price_subtotal');
        $tax = $order->lines->sum('price_tax');
        $total = $order->lines->sum('price_total');

        $order->updateQuietly([
            'amount_untaxed' => $untaxed,
            'amount_tax' => $tax,
            'amount_total' => $total,
        ]);
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['qty_remaining'] = max(0, ($model->product_qty ?? 0) - ($model->qty_received ?? 0));
        $data['qty_to_invoice_remaining'] = max(0, ($model->qty_to_invoice ?? 0) - ($model->qty_invoiced ?? 0));

        // Add project integration context
        if ($model->project_id) {
            $data['project_context'] = [
                'project_id' => $model->project_id,
                'bom_id' => $model->bom_id,
                'hardware_requirement_id' => $model->hardware_requirement_id,
            ];
        }

        return $data;
    }
}
