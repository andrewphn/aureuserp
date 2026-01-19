<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;

/**
 * Bill Controller for V1 API
 *
 * Handles Vendor Bills and Vendor Credit Notes (Refunds).
 * - in_invoice = Vendor Bill
 * - in_refund = Vendor Credit Note/Refund
 *
 * For customer invoices, use InvoiceController instead.
 *
 * Additional endpoints:
 * - POST /bills/{id}/post - Post/confirm bill
 * - POST /bills/{id}/reset-to-draft - Reset to draft
 * - POST /bills/{id}/refund - Create vendor credit note from bill
 */
class BillController extends BaseResourceController
{
    protected string $modelClass = Move::class;

    protected array $searchableFields = [
        'name',
        'ref',
        'narration',
        'payment_reference',
        'invoice_origin',
    ];

    protected array $filterableFields = [
        'id',
        'state',
        'move_type',
        'partner_id',
        'journal_id',
        'company_id',
        'currency_id',
        'invoice_user_id',
        'date',
        'invoice_date',
        'invoice_date_due',
        'payment_state',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'date',
        'invoice_date',
        'invoice_date_due',
        'amount_total',
        'amount_residual',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'partner',
        'lines',
        'lines.product',
        'journal',
        'company',
        'currency',
        'invoiceUser',
        'paymentTerm',
        'fiscalPosition',
        'payments',
        'purchaseOrders',
    ];

    /**
     * Apply default scope to only show bills (not customer invoices)
     */
    protected function applyResourceScope(Builder $query): Builder
    {
        return $query->whereIn('move_type', [MoveType::IN_INVOICE, MoveType::IN_REFUND]);
    }

    protected function validateStore(): array
    {
        return [
            'partner_id' => 'required|integer|exists:partners_partners,id',
            'move_type' => 'nullable|string|in:in_invoice,in_refund',
            'journal_id' => 'nullable|integer|exists:accounts_journals,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'invoice_user_id' => 'nullable|integer|exists:users,id',
            'payment_term_id' => 'nullable|integer|exists:accounts_payment_terms,id',
            'fiscal_position_id' => 'nullable|integer|exists:accounts_fiscal_positions,id',
            'invoice_date' => 'nullable|date',
            'invoice_date_due' => 'nullable|date',
            'date' => 'nullable|date',
            'ref' => 'nullable|string|max:255',
            'narration' => 'nullable|string',
            'payment_reference' => 'nullable|string|max:255',
            'invoice_origin' => 'nullable|string|max:255',
            'invoice_partner_bank_id' => 'nullable|integer|exists:partners_bank_accounts,id',
            'purchase_order_id' => 'nullable|integer|exists:purchases_orders,id',
            'lines' => 'nullable|array',
            'lines.*.product_id' => 'nullable|integer|exists:products_products,id',
            'lines.*.name' => 'required_with:lines|string',
            'lines.*.quantity' => 'required_with:lines|numeric|min:0',
            'lines.*.price_unit' => 'required_with:lines|numeric',
            'lines.*.discount' => 'nullable|numeric|min:0|max:100',
            'lines.*.account_id' => 'nullable|integer|exists:accounts_accounts,id',
            'lines.*.purchase_order_line_id' => 'nullable|integer|exists:purchases_order_lines,id',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'partner_id' => 'sometimes|integer|exists:partners_partners,id',
            'journal_id' => 'nullable|integer|exists:accounts_journals,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'invoice_user_id' => 'nullable|integer|exists:users,id',
            'payment_term_id' => 'nullable|integer|exists:accounts_payment_terms,id',
            'fiscal_position_id' => 'nullable|integer|exists:accounts_fiscal_positions,id',
            'invoice_date' => 'nullable|date',
            'invoice_date_due' => 'nullable|date',
            'date' => 'nullable|date',
            'ref' => 'nullable|string|max:255',
            'narration' => 'nullable|string',
            'payment_reference' => 'nullable|string|max:255',
            'invoice_origin' => 'nullable|string|max:255',
            'invoice_partner_bank_id' => 'nullable|integer|exists:partners_bank_accounts,id',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        if (!isset($data['invoice_user_id'])) {
            $data['invoice_user_id'] = $request->user()->id;
        }

        if (!isset($data['move_type'])) {
            $data['move_type'] = MoveType::IN_INVOICE;
        }

        if (!isset($data['state'])) {
            $data['state'] = MoveState::DRAFT;
        }

        if (!isset($data['date'])) {
            $data['date'] = now();
        }

        return $data;
    }

    protected function afterStore(Model $model, Request $request): void
    {
        // Create bill lines if provided
        if ($request->has('lines') && is_array($request->input('lines'))) {
            foreach ($request->input('lines') as $lineData) {
                $lineData['move_id'] = $model->id;
                $lineData['company_id'] = $model->company_id;
                $lineData['currency_id'] = $model->currency_id;
                $lineData['partner_id'] = $model->partner_id;
                $lineData['journal_id'] = $model->journal_id;
                $lineData['creator_id'] = $request->user()->id;
                $lineData['display_type'] = $lineData['display_type'] ?? 'product';

                // Calculate amounts
                $qty = $lineData['quantity'] ?? 1;
                $price = $lineData['price_unit'] ?? 0;
                $discount = $lineData['discount'] ?? 0;

                $lineData['price_subtotal'] = $qty * $price * (1 - $discount / 100);
                $lineData['price_total'] = $lineData['price_subtotal'];
                $lineData['balance'] = -$lineData['price_subtotal']; // Bills have negative balance
                $lineData['amount_currency'] = -$lineData['price_subtotal'];

                MoveLine::create($lineData);
            }

            // Recalculate totals
            $this->recalculateTotals($model);
        }

        // Link to purchase order if provided
        if ($request->has('purchase_order_id')) {
            $model->purchaseOrders()->sync([$request->input('purchase_order_id')]);
        }
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['is_bill'] = $model->move_type === MoveType::IN_INVOICE;
        $data['is_refund'] = $model->move_type === MoveType::IN_REFUND;
        $data['is_draft'] = $model->state === MoveState::DRAFT;
        $data['is_posted'] = $model->state === MoveState::POSTED;
        $data['is_cancelled'] = $model->state === MoveState::CANCEL;
        $data['is_paid'] = ($model->amount_residual ?? 0) == 0 && $model->state === MoveState::POSTED;

        return $data;
    }

    /**
     * Post/confirm a bill
     *
     * POST /api/v1/bills/{id}/post
     */
    public function post(int $id): JsonResponse
    {
        $bill = Move::find($id);

        if (!$bill) {
            return $this->notFound('Bill not found');
        }

        if (!in_array($bill->move_type, [MoveType::IN_INVOICE, MoveType::IN_REFUND])) {
            return $this->error('This is not a bill', 422);
        }

        if ($bill->state !== MoveState::DRAFT) {
            return $this->error('Only draft bills can be posted', 422);
        }

        // Check if has lines
        if ($bill->lines()->where('display_type', 'product')->count() === 0) {
            return $this->error('Bill must have at least one line item', 422);
        }

        // Require invoice date for bills
        if (!$bill->invoice_date) {
            return $this->error('Bill date is required', 422);
        }

        $bill->update([
            'state' => MoveState::POSTED,
            'posted_before' => true,
        ]);

        // Generate name/number if not set
        if (!$bill->name || $bill->name === '/') {
            $prefix = $bill->move_type === MoveType::IN_INVOICE ? 'BILL/' : 'RBILL/';
            $bill->update([
                'name' => $prefix . date('Y/') . str_pad($bill->id, 5, '0', STR_PAD_LEFT),
            ]);
        }

        $this->dispatchWebhookEvent($bill->fresh(), 'posted');

        return $this->success(
            $this->transformModel($bill->fresh()),
            'Bill posted'
        );
    }

    /**
     * Reset bill to draft
     *
     * POST /api/v1/bills/{id}/reset-to-draft
     */
    public function resetToDraft(int $id): JsonResponse
    {
        $bill = Move::find($id);

        if (!$bill) {
            return $this->notFound('Bill not found');
        }

        if ($bill->state === MoveState::DRAFT) {
            return $this->error('Bill is already in draft state', 422);
        }

        // Cannot reset if payments exist
        if ($bill->payments()->exists()) {
            return $this->error('Cannot reset to draft - bill has payments', 422);
        }

        $bill->update([
            'state' => MoveState::DRAFT,
        ]);

        $this->dispatchWebhookEvent($bill->fresh(), 'reset_to_draft');

        return $this->success(
            $this->transformModel($bill->fresh()),
            'Bill reset to draft'
        );
    }

    /**
     * Cancel a bill
     *
     * POST /api/v1/bills/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        $bill = Move::find($id);

        if (!$bill) {
            return $this->notFound('Bill not found');
        }

        if ($bill->state === MoveState::CANCEL) {
            return $this->error('Bill is already cancelled', 422);
        }

        // Cannot cancel if payments exist
        if ($bill->payments()->where('state', '!=', 'cancelled')->exists()) {
            return $this->error('Cannot cancel - bill has active payments', 422);
        }

        $bill->update([
            'state' => MoveState::CANCEL,
        ]);

        $this->dispatchWebhookEvent($bill->fresh(), 'cancelled');

        return $this->success(
            $this->transformModel($bill->fresh()),
            'Bill cancelled'
        );
    }

    /**
     * Create a vendor credit note/refund from a bill
     *
     * POST /api/v1/bills/{id}/refund
     */
    public function createRefund(int $id, Request $request): JsonResponse
    {
        $bill = Move::with('lines')->find($id);

        if (!$bill) {
            return $this->notFound('Bill not found');
        }

        if ($bill->move_type !== MoveType::IN_INVOICE) {
            return $this->error('Refunds can only be created from bills', 422);
        }

        if ($bill->state !== MoveState::POSTED) {
            return $this->error('Only posted bills can have refunds', 422);
        }

        // Create the refund
        $refund = Move::create([
            'move_type' => MoveType::IN_REFUND,
            'state' => MoveState::DRAFT,
            'partner_id' => $bill->partner_id,
            'journal_id' => $bill->journal_id,
            'company_id' => $bill->company_id,
            'currency_id' => $bill->currency_id,
            'invoice_user_id' => $request->user()->id,
            'date' => now(),
            'invoice_date' => now(),
            'ref' => 'Refund of ' . $bill->name,
            'invoice_origin' => $bill->name,
            'creator_id' => $request->user()->id,
        ]);

        // Copy lines
        foreach ($bill->lines as $line) {
            if ($line->display_type !== 'product') {
                continue;
            }

            MoveLine::create([
                'move_id' => $refund->id,
                'product_id' => $line->product_id,
                'account_id' => $line->account_id,
                'partner_id' => $line->partner_id,
                'journal_id' => $refund->journal_id,
                'company_id' => $refund->company_id,
                'currency_id' => $refund->currency_id,
                'name' => $line->name,
                'quantity' => $line->quantity,
                'price_unit' => $line->price_unit,
                'discount' => $line->discount,
                'price_subtotal' => $line->price_subtotal,
                'price_total' => $line->price_total,
                'display_type' => 'product',
                'creator_id' => $request->user()->id,
            ]);
        }

        $this->recalculateTotals($refund);
        $this->dispatchWebhookEvent($refund, 'created');

        return $this->success(
            $this->transformModel($refund->fresh()),
            'Vendor credit note created',
            201
        );
    }

    /**
     * Create a bill from a purchase order
     *
     * POST /api/v1/bills/from-purchase-order/{purchaseOrderId}
     */
    public function createFromPurchaseOrder(int $purchaseOrderId, Request $request): JsonResponse
    {
        $purchaseOrder = \Webkul\Purchase\Models\Order::with('lines', 'partner')->find($purchaseOrderId);

        if (!$purchaseOrder) {
            return $this->notFound('Purchase order not found');
        }

        if ($purchaseOrder->state !== \Webkul\Purchase\Enums\OrderState::PURCHASE) {
            return $this->error('Only confirmed purchase orders can be billed', 422);
        }

        // Get lines to bill (not yet invoiced)
        $linesToBill = $purchaseOrder->lines()->where('qty_to_invoice', '>', 0)->get();

        if ($linesToBill->isEmpty()) {
            return $this->error('No lines to bill', 422);
        }

        // Create the bill
        $bill = Move::create([
            'move_type' => MoveType::IN_INVOICE,
            'state' => MoveState::DRAFT,
            'partner_id' => $purchaseOrder->partner_id,
            'company_id' => $purchaseOrder->company_id,
            'currency_id' => $purchaseOrder->currency_id,
            'invoice_user_id' => $request->user()->id,
            'date' => now(),
            'invoice_origin' => $purchaseOrder->name,
            'creator_id' => $request->user()->id,
        ]);

        // Create lines
        foreach ($linesToBill as $poLine) {
            MoveLine::create([
                'move_id' => $bill->id,
                'product_id' => $poLine->product_id,
                'partner_id' => $bill->partner_id,
                'company_id' => $bill->company_id,
                'currency_id' => $bill->currency_id,
                'name' => $poLine->name ?? $poLine->product?->name ?? 'Product',
                'quantity' => $poLine->qty_to_invoice,
                'price_unit' => $poLine->price_unit,
                'discount' => $poLine->discount,
                'price_subtotal' => $poLine->qty_to_invoice * $poLine->price_unit * (1 - ($poLine->discount ?? 0) / 100),
                'price_total' => $poLine->qty_to_invoice * $poLine->price_unit * (1 - ($poLine->discount ?? 0) / 100),
                'display_type' => 'product',
                'purchase_order_line_id' => $poLine->id,
                'creator_id' => $request->user()->id,
            ]);
        }

        // Link to PO
        $bill->purchaseOrders()->sync([$purchaseOrderId]);

        $this->recalculateTotals($bill);
        $this->dispatchWebhookEvent($bill, 'created');

        return $this->success(
            $this->transformModel($bill->fresh()),
            'Bill created from purchase order',
            201
        );
    }

    /**
     * Recalculate bill totals based on lines
     */
    protected function recalculateTotals(Move $move): void
    {
        $move->load('lines');

        $untaxed = $move->lines->where('display_type', 'product')->sum('price_subtotal');
        $tax = $move->lines->where('display_type', 'tax')->sum('price_subtotal');
        $total = $untaxed + $tax;

        $move->updateQuietly([
            'amount_untaxed' => $untaxed,
            'amount_tax' => $tax,
            'amount_total' => $total,
            'amount_residual' => $move->state === MoveState::DRAFT ? $total : ($move->amount_residual ?? $total),
        ]);
    }
}
