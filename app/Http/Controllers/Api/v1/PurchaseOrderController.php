<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Enums\InvoiceStatus;

/**
 * Purchase Order Controller for V1 API
 *
 * Handles Purchase Orders and Requests for Quotation (RFQ).
 * - Draft/Sent/To Approve states = RFQ
 * - Purchase state = Confirmed Purchase Order
 *
 * Additional endpoints:
 * - POST /purchase-orders/{id}/confirm - Confirm an RFQ into a PO
 * - POST /purchase-orders/{id}/cancel - Cancel an order
 * - POST /purchase-orders/{id}/send - Mark as sent
 */
class PurchaseOrderController extends BaseResourceController
{
    protected string $modelClass = Order::class;

    protected array $searchableFields = [
        'name',
        'partner_ref',
        'origin',
        'notes',
    ];

    protected array $filterableFields = [
        'id',
        'state',
        'invoice_status',
        'partner_id',
        'project_id',
        'user_id',
        'company_id',
        'currency_id',
        'picking_type_id',
        'date_order',
        'date_approve',
        'date_planned',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'date_order',
        'date_approve',
        'date_planned',
        'amount_total',
        'amount_untaxed',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'partner',
        'project',
        'lines',
        'lines.product',
        'user',
        'company',
        'currency',
        'pickingType',
        'accountMoves',
        'operations',
        'requisitions',
    ];

    protected function validateStore(): array
    {
        return [
            'partner_id' => 'required|integer|exists:partners_partners,id',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'picking_type_id' => 'nullable|integer|exists:inventories_picking_types,id',
            'fiscal_position_id' => 'nullable|integer|exists:accounts_fiscal_positions,id',
            'payment_term_id' => 'nullable|integer|exists:accounts_payment_terms,id',
            'incoterm_id' => 'nullable|integer|exists:accounts_incoterms,id',
            'state' => 'nullable|string|in:draft,sent,to_approve,purchase,done,cancel',
            'date_order' => 'nullable|date',
            'date_planned' => 'nullable|date',
            'partner_ref' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'incoterm_location' => 'nullable|string|max:255',
            'lines' => 'nullable|array',
            'lines.*.product_id' => 'required_with:lines|integer|exists:products_products,id',
            'lines.*.product_qty' => 'required_with:lines|numeric|min:0',
            'lines.*.price_unit' => 'nullable|numeric|min:0',
            'lines.*.discount' => 'nullable|numeric|min:0|max:100',
            'lines.*.name' => 'nullable|string',
            'lines.*.project_id' => 'nullable|integer|exists:projects_projects,id',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'partner_id' => 'sometimes|integer|exists:partners_partners,id',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'picking_type_id' => 'nullable|integer|exists:inventories_picking_types,id',
            'fiscal_position_id' => 'nullable|integer|exists:accounts_fiscal_positions,id',
            'payment_term_id' => 'nullable|integer|exists:accounts_payment_terms,id',
            'incoterm_id' => 'nullable|integer|exists:accounts_incoterms,id',
            'state' => 'nullable|string|in:draft,sent,to_approve,purchase,done,cancel',
            'date_order' => 'nullable|date',
            'date_planned' => 'nullable|date',
            'partner_ref' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'incoterm_location' => 'nullable|string|max:255',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        if (!isset($data['user_id'])) {
            $data['user_id'] = $request->user()->id;
        }

        if (!isset($data['state'])) {
            $data['state'] = OrderState::DRAFT;
        }

        if (!isset($data['invoice_status'])) {
            $data['invoice_status'] = InvoiceStatus::NO;
        }

        if (!isset($data['date_order'])) {
            $data['date_order'] = now();
        }

        return $data;
    }

    protected function afterStore(Model $model, Request $request): void
    {
        // Create order lines if provided
        if ($request->has('lines') && is_array($request->input('lines'))) {
            foreach ($request->input('lines') as $lineData) {
                $lineData['order_id'] = $model->id;
                $lineData['company_id'] = $model->company_id;
                $lineData['currency_id'] = $model->currency_id;
                $lineData['partner_id'] = $model->partner_id;
                $lineData['creator_id'] = $request->user()->id;
                $lineData['state'] = $model->state;

                \Webkul\Purchase\Models\OrderLine::create($lineData);
            }

            // Recalculate totals
            $this->recalculateTotals($model);
        }
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['is_rfq'] = in_array($model->state, [OrderState::DRAFT, OrderState::SENT, OrderState::TO_APPROVE]);
        $data['is_confirmed'] = $model->state === OrderState::PURCHASE;
        $data['is_done'] = $model->state === OrderState::DONE;
        $data['qty_to_receive'] = $model->qty_to_receive ?? 0;
        $data['qty_received'] = $model->qty_received ?? 0;

        return $data;
    }

    /**
     * Confirm an RFQ into a Purchase Order
     *
     * POST /api/v1/purchase-orders/{id}/confirm
     */
    public function confirm(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Purchase order not found');
        }

        if (!in_array($order->state, [OrderState::DRAFT, OrderState::SENT, OrderState::TO_APPROVE])) {
            return $this->error('Only draft, sent, or pending approval orders can be confirmed', 422);
        }

        $order->update([
            'state' => OrderState::PURCHASE,
            'date_approve' => now(),
            'date_order' => $order->date_order ?? now(),
        ]);

        $this->dispatchWebhookEvent($order->fresh(), 'confirmed');

        return $this->success(
            $this->transformModel($order->fresh()),
            'RFQ confirmed as purchase order'
        );
    }

    /**
     * Cancel an order
     *
     * POST /api/v1/purchase-orders/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Purchase order not found');
        }

        if ($order->state === OrderState::CANCEL) {
            return $this->error('Order is already cancelled', 422);
        }

        // Check if there are any received quantities
        $hasReceived = $order->lines()->where('qty_received', '>', 0)->exists();
        if ($hasReceived) {
            return $this->error('Cannot cancel order with received quantities', 422);
        }

        $order->update([
            'state' => OrderState::CANCEL,
        ]);

        $this->dispatchWebhookEvent($order->fresh(), 'cancelled');

        return $this->success(
            $this->transformModel($order->fresh()),
            'Order cancelled'
        );
    }

    /**
     * Mark RFQ as sent
     *
     * POST /api/v1/purchase-orders/{id}/send
     */
    public function send(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Purchase order not found');
        }

        if ($order->state !== OrderState::DRAFT) {
            return $this->error('Only draft orders can be marked as sent', 422);
        }

        $order->update([
            'state' => OrderState::SENT,
        ]);

        $this->dispatchWebhookEvent($order->fresh(), 'sent');

        return $this->success(
            $this->transformModel($order->fresh()),
            'RFQ marked as sent'
        );
    }

    /**
     * Reset order to draft
     *
     * POST /api/v1/purchase-orders/{id}/reset-to-draft
     */
    public function resetToDraft(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Purchase order not found');
        }

        if ($order->state === OrderState::DRAFT) {
            return $this->error('Order is already in draft state', 422);
        }

        // Cannot reset if bills exist
        if ($order->accountMoves()->exists()) {
            return $this->error('Cannot reset to draft - order has bills', 422);
        }

        // Cannot reset if items received
        $hasReceived = $order->lines()->where('qty_received', '>', 0)->exists();
        if ($hasReceived) {
            return $this->error('Cannot reset to draft - items have been received', 422);
        }

        $order->update([
            'state' => OrderState::DRAFT,
            'date_approve' => null,
        ]);

        $this->dispatchWebhookEvent($order->fresh(), 'reset_to_draft');

        return $this->success(
            $this->transformModel($order->fresh()),
            'Order reset to draft'
        );
    }

    /**
     * Mark order as done (fully received and billed)
     *
     * POST /api/v1/purchase-orders/{id}/done
     */
    public function done(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Purchase order not found');
        }

        if ($order->state !== OrderState::PURCHASE) {
            return $this->error('Only confirmed purchase orders can be marked as done', 422);
        }

        $order->update([
            'state' => OrderState::DONE,
        ]);

        $this->dispatchWebhookEvent($order->fresh(), 'done');

        return $this->success(
            $this->transformModel($order->fresh()),
            'Order marked as done'
        );
    }

    /**
     * Recalculate order totals based on lines
     */
    protected function recalculateTotals(Order $order): void
    {
        $order->load('lines');

        $untaxed = $order->lines->sum('price_subtotal');
        $tax = $order->lines->sum('price_tax');
        $total = $order->lines->sum('price_total');

        $order->updateQuietly([
            'amount_untaxed' => $untaxed,
            'amount_tax' => $tax,
            'amount_total' => $total,
        ]);
    }
}
