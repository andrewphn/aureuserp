<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Enums\InvoiceStatus;

/**
 * Sales Order Controller for V1 API
 *
 * Handles both Quotes and Sales Orders (differentiated by state).
 * - Draft/Sent states = Quotes
 * - Sale state = Sales Orders
 *
 * Additional endpoints:
 * - POST /sales-orders/{id}/confirm - Confirm a quote into a sales order
 * - POST /sales-orders/{id}/cancel - Cancel an order
 * - POST /sales-orders/{id}/send - Mark as sent
 */
class SalesOrderController extends BaseResourceController
{
    protected string $modelClass = Order::class;

    protected array $searchableFields = [
        'name',
        'client_order_ref',
        'reference',
        'origin',
        'note',
    ];

    protected array $filterableFields = [
        'id',
        'state',
        'invoice_status',
        'partner_id',
        'project_id',
        'user_id',
        'team_id',
        'company_id',
        'warehouse_id',
        'currency_id',
        'date_order',
        'validity_date',
        'commitment_date',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'date_order',
        'validity_date',
        'commitment_date',
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
        'team',
        'company',
        'currency',
        'warehouse',
        'partnerInvoice',
        'partnerShipping',
        'tags',
        'accountMoves',
        'operations',
    ];

    protected function validateStore(): array
    {
        return [
            'partner_id' => 'required|integer|exists:partners_partners,id',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'warehouse_id' => 'nullable|integer|exists:inventories_warehouses,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'team_id' => 'nullable|integer|exists:sales_teams,id',
            'partner_invoice_id' => 'nullable|integer|exists:partners_partners,id',
            'partner_shipping_id' => 'nullable|integer|exists:partners_partners,id',
            'payment_term_id' => 'nullable|integer|exists:accounts_payment_terms,id',
            'fiscal_position_id' => 'nullable|integer|exists:accounts_fiscal_positions,id',
            'state' => 'nullable|string|in:draft,sent,sale,cancel',
            'date_order' => 'nullable|date',
            'validity_date' => 'nullable|date',
            'commitment_date' => 'nullable|date',
            'client_order_ref' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'require_signature' => 'nullable|boolean',
            'require_payment' => 'nullable|boolean',
            'prepayment_percent' => 'nullable|numeric|min:0|max:100',
            'lines' => 'nullable|array',
            'lines.*.product_id' => 'required_with:lines|integer|exists:products_products,id',
            'lines.*.product_uom_qty' => 'required_with:lines|numeric|min:0',
            'lines.*.price_unit' => 'nullable|numeric|min:0',
            'lines.*.discount' => 'nullable|numeric|min:0|max:100',
            'lines.*.name' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'partner_id' => 'sometimes|integer|exists:partners_partners,id',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'warehouse_id' => 'nullable|integer|exists:inventories_warehouses,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'team_id' => 'nullable|integer|exists:sales_teams,id',
            'partner_invoice_id' => 'nullable|integer|exists:partners_partners,id',
            'partner_shipping_id' => 'nullable|integer|exists:partners_partners,id',
            'payment_term_id' => 'nullable|integer|exists:accounts_payment_terms,id',
            'fiscal_position_id' => 'nullable|integer|exists:accounts_fiscal_positions,id',
            'state' => 'nullable|string|in:draft,sent,sale,cancel',
            'date_order' => 'nullable|date',
            'validity_date' => 'nullable|date',
            'commitment_date' => 'nullable|date',
            'client_order_ref' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'require_signature' => 'nullable|boolean',
            'require_payment' => 'nullable|boolean',
            'prepayment_percent' => 'nullable|numeric|min:0|max:100',
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

        // Default validity to 30 days for quotes
        if (!isset($data['validity_date']) && $data['state'] === OrderState::DRAFT) {
            $data['validity_date'] = now()->addDays(30);
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
                $lineData['order_partner_id'] = $model->partner_id;
                $lineData['salesman_id'] = $model->user_id;
                $lineData['creator_id'] = $request->user()->id;
                $lineData['state'] = $model->state;

                \Webkul\Sale\Models\OrderLine::create($lineData);
            }

            // Recalculate totals
            $this->recalculateTotals($model);
        }
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['is_quote'] = in_array($model->state, [OrderState::DRAFT, OrderState::SENT]);
        $data['is_confirmed'] = $model->state === OrderState::SALE;
        $data['qty_to_invoice'] = $model->qty_to_invoice ?? 0;

        return $data;
    }

    /**
     * Confirm a quote into a sales order
     *
     * POST /api/v1/sales-orders/{id}/confirm
     */
    public function confirm(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Sales order not found');
        }

        if (!in_array($order->state, [OrderState::DRAFT, OrderState::SENT])) {
            return $this->error('Only draft or sent quotes can be confirmed', 422);
        }

        $order->update([
            'state' => OrderState::SALE,
            'date_order' => $order->date_order ?? now(),
        ]);

        $this->dispatchWebhookEvent($order->fresh(), 'confirmed');

        return $this->success(
            $this->transformModel($order->fresh()),
            'Quote confirmed as sales order'
        );
    }

    /**
     * Cancel an order
     *
     * POST /api/v1/sales-orders/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Sales order not found');
        }

        if ($order->state === OrderState::CANCEL) {
            return $this->error('Order is already cancelled', 422);
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
     * Mark quote as sent
     *
     * POST /api/v1/sales-orders/{id}/send
     */
    public function send(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Sales order not found');
        }

        if ($order->state !== OrderState::DRAFT) {
            return $this->error('Only draft quotes can be marked as sent', 422);
        }

        $order->update([
            'state' => OrderState::SENT,
        ]);

        $this->dispatchWebhookEvent($order->fresh(), 'sent');

        return $this->success(
            $this->transformModel($order->fresh()),
            'Quote marked as sent'
        );
    }

    /**
     * Reset order to draft
     *
     * POST /api/v1/sales-orders/{id}/reset-to-draft
     */
    public function resetToDraft(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->notFound('Sales order not found');
        }

        if ($order->state === OrderState::DRAFT) {
            return $this->error('Order is already in draft state', 422);
        }

        // Cannot reset if invoices exist
        if ($order->accountMoves()->exists()) {
            return $this->error('Cannot reset to draft - order has invoices', 422);
        }

        $order->update([
            'state' => OrderState::DRAFT,
        ]);

        $this->dispatchWebhookEvent($order->fresh(), 'reset_to_draft');

        return $this->success(
            $this->transformModel($order->fresh()),
            'Order reset to draft'
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
