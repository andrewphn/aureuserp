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
 * Invoice Controller for V1 API
 *
 * Handles Customer Invoices and Credit Notes.
 * - out_invoice = Customer Invoice
 * - out_refund = Credit Note
 *
 * For vendor bills, use BillController instead.
 *
 * Additional endpoints:
 * - POST /invoices/{id}/post - Post/confirm invoice
 * - POST /invoices/{id}/reset-to-draft - Reset to draft
 * - POST /invoices/{id}/credit-note - Create credit note from invoice
 */
class InvoiceController extends BaseResourceController
{
    protected string $modelClass = Move::class;

    protected array $searchableFields = [
        'name',
        'ref',
        'narration',
        'payment_reference',
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
        'salesOrders',
        'purchaseOrders',
    ];

    /**
     * Apply default scope to only show invoices (not bills)
     */
    protected function applyResourceScope(Builder $query): Builder
    {
        return $query->whereIn('move_type', [MoveType::OUT_INVOICE, MoveType::OUT_REFUND]);
    }

    protected function validateStore(): array
    {
        return [
            'partner_id' => 'required|integer|exists:partners_partners,id',
            'move_type' => 'nullable|string|in:out_invoice,out_refund',
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
            'lines' => 'nullable|array',
            'lines.*.product_id' => 'nullable|integer|exists:products_products,id',
            'lines.*.name' => 'required_with:lines|string',
            'lines.*.quantity' => 'required_with:lines|numeric|min:0',
            'lines.*.price_unit' => 'required_with:lines|numeric',
            'lines.*.discount' => 'nullable|numeric|min:0|max:100',
            'lines.*.account_id' => 'nullable|integer|exists:accounts_accounts,id',
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
            $data['move_type'] = MoveType::OUT_INVOICE;
        }

        if (!isset($data['state'])) {
            $data['state'] = MoveState::DRAFT;
        }

        if (!isset($data['date'])) {
            $data['date'] = now();
        }

        if (!isset($data['invoice_date'])) {
            $data['invoice_date'] = now();
        }

        return $data;
    }

    protected function afterStore(Model $model, Request $request): void
    {
        // Create invoice lines if provided
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
                $lineData['balance'] = $lineData['price_subtotal'];
                $lineData['amount_currency'] = $lineData['price_subtotal'];

                MoveLine::create($lineData);
            }

            // Recalculate totals
            $this->recalculateTotals($model);
        }
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['is_invoice'] = $model->move_type === MoveType::OUT_INVOICE;
        $data['is_credit_note'] = $model->move_type === MoveType::OUT_REFUND;
        $data['is_draft'] = $model->state === MoveState::DRAFT;
        $data['is_posted'] = $model->state === MoveState::POSTED;
        $data['is_cancelled'] = $model->state === MoveState::CANCEL;
        $data['is_paid'] = ($model->amount_residual ?? 0) == 0 && $model->state === MoveState::POSTED;

        return $data;
    }

    /**
     * Post/confirm an invoice
     *
     * POST /api/v1/invoices/{id}/post
     */
    public function post(int $id): JsonResponse
    {
        $invoice = Move::find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        if (!in_array($invoice->move_type, [MoveType::OUT_INVOICE, MoveType::OUT_REFUND])) {
            return $this->error('This is not an invoice', 422);
        }

        if ($invoice->state !== MoveState::DRAFT) {
            return $this->error('Only draft invoices can be posted', 422);
        }

        // Check if has lines
        if ($invoice->lines()->where('display_type', 'product')->count() === 0) {
            return $this->error('Invoice must have at least one line item', 422);
        }

        $invoice->update([
            'state' => MoveState::POSTED,
            'posted_before' => true,
        ]);

        // Generate name/number if not set
        if (!$invoice->name || $invoice->name === '/') {
            $prefix = $invoice->move_type === MoveType::OUT_INVOICE ? 'INV/' : 'RINV/';
            $invoice->update([
                'name' => $prefix . date('Y/') . str_pad($invoice->id, 5, '0', STR_PAD_LEFT),
            ]);
        }

        $this->dispatchWebhookEvent($invoice->fresh(), 'posted');

        return $this->success(
            $this->transformModel($invoice->fresh()),
            'Invoice posted'
        );
    }

    /**
     * Reset invoice to draft
     *
     * POST /api/v1/invoices/{id}/reset-to-draft
     */
    public function resetToDraft(int $id): JsonResponse
    {
        $invoice = Move::find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        if ($invoice->state === MoveState::DRAFT) {
            return $this->error('Invoice is already in draft state', 422);
        }

        // Cannot reset if payments exist
        if ($invoice->payments()->exists()) {
            return $this->error('Cannot reset to draft - invoice has payments', 422);
        }

        $invoice->update([
            'state' => MoveState::DRAFT,
        ]);

        $this->dispatchWebhookEvent($invoice->fresh(), 'reset_to_draft');

        return $this->success(
            $this->transformModel($invoice->fresh()),
            'Invoice reset to draft'
        );
    }

    /**
     * Cancel an invoice
     *
     * POST /api/v1/invoices/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        $invoice = Move::find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        if ($invoice->state === MoveState::CANCEL) {
            return $this->error('Invoice is already cancelled', 422);
        }

        // Cannot cancel if payments exist
        if ($invoice->payments()->where('state', '!=', 'cancelled')->exists()) {
            return $this->error('Cannot cancel - invoice has active payments', 422);
        }

        $invoice->update([
            'state' => MoveState::CANCEL,
        ]);

        $this->dispatchWebhookEvent($invoice->fresh(), 'cancelled');

        return $this->success(
            $this->transformModel($invoice->fresh()),
            'Invoice cancelled'
        );
    }

    /**
     * Create a credit note from an invoice
     *
     * POST /api/v1/invoices/{id}/credit-note
     */
    public function createCreditNote(int $id, Request $request): JsonResponse
    {
        $invoice = Move::with('lines')->find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        if ($invoice->move_type !== MoveType::OUT_INVOICE) {
            return $this->error('Credit notes can only be created from invoices', 422);
        }

        if ($invoice->state !== MoveState::POSTED) {
            return $this->error('Only posted invoices can have credit notes', 422);
        }

        // Create the credit note
        $creditNote = Move::create([
            'move_type' => MoveType::OUT_REFUND,
            'state' => MoveState::DRAFT,
            'partner_id' => $invoice->partner_id,
            'journal_id' => $invoice->journal_id,
            'company_id' => $invoice->company_id,
            'currency_id' => $invoice->currency_id,
            'invoice_user_id' => $request->user()->id,
            'date' => now(),
            'invoice_date' => now(),
            'ref' => 'Reversal of ' . $invoice->name,
            'invoice_origin' => $invoice->name,
            'creator_id' => $request->user()->id,
        ]);

        // Copy lines with reversed amounts
        foreach ($invoice->lines as $line) {
            if ($line->display_type !== 'product') {
                continue;
            }

            MoveLine::create([
                'move_id' => $creditNote->id,
                'product_id' => $line->product_id,
                'account_id' => $line->account_id,
                'partner_id' => $line->partner_id,
                'journal_id' => $creditNote->journal_id,
                'company_id' => $creditNote->company_id,
                'currency_id' => $creditNote->currency_id,
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

        $this->recalculateTotals($creditNote);
        $this->dispatchWebhookEvent($creditNote, 'created');

        return $this->success(
            $this->transformModel($creditNote->fresh()),
            'Credit note created',
            201
        );
    }

    /**
     * Recalculate invoice totals based on lines
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
