<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Account\Models\Payment;
use Webkul\Account\Models\Move;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;

/**
 * Payment Controller for V1 API
 *
 * Handles payment registration for invoices and bills.
 * - inbound = Customer payment (for invoices)
 * - outbound = Vendor payment (for bills)
 *
 * Additional endpoints:
 * - POST /payments/{id}/post - Post/confirm payment
 * - POST /payments/{id}/cancel - Cancel payment
 * - POST /payments/register - Register payment for invoice/bill
 */
class PaymentController extends BaseResourceController
{
    protected string $modelClass = Payment::class;

    protected array $searchableFields = [
        'name',
        'memo',
        'payment_reference',
    ];

    protected array $filterableFields = [
        'id',
        'state',
        'payment_type',
        'partner_type',
        'partner_id',
        'journal_id',
        'company_id',
        'currency_id',
        'payment_method_id',
        'date',
        'is_reconciled',
        'is_matched',
        'is_sent',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'date',
        'amount',
        'amount_company_currency_signed',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'partner',
        'move',
        'journal',
        'company',
        'currency',
        'paymentMethod',
        'paymentMethodLine',
        'partnerBank',
        'destinationAccount',
        'outstandingAccount',
        'accountMovePayment',
    ];

    protected function validateStore(): array
    {
        return [
            'partner_id' => 'required|integer|exists:partners_partners,id',
            'payment_type' => 'required|string|in:inbound,outbound',
            'partner_type' => 'nullable|string|in:customer,supplier',
            'amount' => 'required|numeric|min:0.01',
            'journal_id' => 'required|integer|exists:accounts_journals,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'payment_method_id' => 'nullable|integer|exists:accounts_payment_methods,id',
            'payment_method_line_id' => 'nullable|integer|exists:accounts_payment_method_lines,id',
            'partner_bank_id' => 'nullable|integer|exists:partners_bank_accounts,id',
            'destination_account_id' => 'nullable|integer|exists:accounts_accounts,id',
            'outstanding_account_id' => 'nullable|integer|exists:accounts_accounts,id',
            'date' => 'nullable|date',
            'memo' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'integer|exists:accounts_account_moves,id',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'memo' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['created_by'])) {
            $data['created_by'] = $request->user()->id;
        }

        if (!isset($data['state'])) {
            $data['state'] = 'draft';
        }

        if (!isset($data['date'])) {
            $data['date'] = now();
        }

        // Set partner type based on payment type if not specified
        if (!isset($data['partner_type'])) {
            $data['partner_type'] = $data['payment_type'] === 'inbound' ? 'customer' : 'supplier';
        }

        return $data;
    }

    protected function afterStore(Model $model, Request $request): void
    {
        // Link to invoices/bills if provided
        if ($request->has('invoice_ids') && is_array($request->input('invoice_ids'))) {
            $model->accountMovePayment()->sync($request->input('invoice_ids'));
        }
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['is_customer_payment'] = $model->payment_type === 'inbound';
        $data['is_vendor_payment'] = $model->payment_type === 'outbound';
        $data['is_draft'] = $model->state === 'draft';
        $data['is_posted'] = $model->state === 'posted';
        $data['is_cancelled'] = $model->state === 'cancelled';

        return $data;
    }

    /**
     * Register a payment for invoice(s) or bill(s)
     *
     * POST /api/v1/payments/register
     *
     * Convenience endpoint that creates and posts a payment in one step.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'integer|exists:accounts_account_moves,id',
            'amount' => 'nullable|numeric|min:0.01',
            'journal_id' => 'required|integer|exists:accounts_journals,id',
            'payment_method_id' => 'nullable|integer|exists:accounts_payment_methods,id',
            'date' => 'nullable|date',
            'memo' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        // Get the invoices/bills
        $moves = Move::whereIn('id', $validated['invoice_ids'])
            ->where('state', MoveState::POSTED)
            ->get();

        if ($moves->isEmpty()) {
            return $this->error('No valid posted invoices/bills found', 422);
        }

        // Determine payment type from first move
        $firstMove = $moves->first();
        $isInvoice = in_array($firstMove->move_type, [MoveType::OUT_INVOICE, MoveType::OUT_REFUND]);
        $paymentType = $isInvoice ? 'inbound' : 'outbound';
        $partnerType = $isInvoice ? 'customer' : 'supplier';

        // Calculate total amount if not specified
        $amount = $validated['amount'] ?? $moves->sum('amount_residual');

        if ($amount <= 0) {
            return $this->error('Payment amount must be positive', 422);
        }

        // Check all moves belong to same partner
        $partnerId = $firstMove->partner_id;
        $mismatchedPartner = $moves->contains(fn($m) => $m->partner_id !== $partnerId);
        if ($mismatchedPartner) {
            return $this->error('All invoices/bills must belong to the same partner', 422);
        }

        // Create the payment
        $payment = Payment::create([
            'partner_id' => $partnerId,
            'payment_type' => $paymentType,
            'partner_type' => $partnerType,
            'amount' => $amount,
            'journal_id' => $validated['journal_id'],
            'company_id' => $firstMove->company_id,
            'currency_id' => $firstMove->currency_id,
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'date' => $validated['date'] ?? now(),
            'memo' => $validated['memo'] ?? 'Payment for ' . $moves->pluck('name')->implode(', '),
            'payment_reference' => $validated['payment_reference'] ?? null,
            'state' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        // Link to invoices/bills
        $payment->accountMovePayment()->sync($validated['invoice_ids']);

        // Auto-post the payment
        $payment->update([
            'state' => 'posted',
        ]);

        // Generate payment name
        $prefix = $paymentType === 'inbound' ? 'CUST.IN/' : 'SUPP.OUT/';
        $payment->update([
            'name' => $prefix . date('Y/') . str_pad($payment->id, 5, '0', STR_PAD_LEFT),
        ]);

        // Update residual amounts on moves
        $this->reconcilePayment($payment, $moves, $amount);

        $this->dispatchWebhookEvent($payment->fresh(), 'posted');

        return $this->success(
            $this->transformModel($payment->fresh()),
            'Payment registered and posted',
            201
        );
    }

    /**
     * Post/confirm a payment
     *
     * POST /api/v1/payments/{id}/post
     */
    public function post(int $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return $this->notFound('Payment not found');
        }

        if ($payment->state !== 'draft') {
            return $this->error('Only draft payments can be posted', 422);
        }

        if ($payment->amount <= 0) {
            return $this->error('Payment amount must be positive', 422);
        }

        $payment->update([
            'state' => 'posted',
        ]);

        // Generate name if not set
        if (!$payment->name || $payment->name === '/') {
            $prefix = $payment->payment_type === 'inbound' ? 'CUST.IN/' : 'SUPP.OUT/';
            $payment->update([
                'name' => $prefix . date('Y/') . str_pad($payment->id, 5, '0', STR_PAD_LEFT),
            ]);
        }

        // Reconcile with linked invoices
        $moves = $payment->accountMovePayment;
        if ($moves->isNotEmpty()) {
            $this->reconcilePayment($payment, $moves, $payment->amount);
        }

        $this->dispatchWebhookEvent($payment->fresh(), 'posted');

        return $this->success(
            $this->transformModel($payment->fresh()),
            'Payment posted'
        );
    }

    /**
     * Cancel a payment
     *
     * POST /api/v1/payments/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return $this->notFound('Payment not found');
        }

        if ($payment->state === 'cancelled') {
            return $this->error('Payment is already cancelled', 422);
        }

        // Reverse the reconciliation
        if ($payment->state === 'posted') {
            $this->unreconciledPayment($payment);
        }

        $payment->update([
            'state' => 'cancelled',
        ]);

        $this->dispatchWebhookEvent($payment->fresh(), 'cancelled');

        return $this->success(
            $this->transformModel($payment->fresh()),
            'Payment cancelled'
        );
    }

    /**
     * Reset payment to draft
     *
     * POST /api/v1/payments/{id}/reset-to-draft
     */
    public function resetToDraft(int $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return $this->notFound('Payment not found');
        }

        if ($payment->state === 'draft') {
            return $this->error('Payment is already in draft state', 422);
        }

        // Cannot reset if reconciled
        if ($payment->is_reconciled) {
            return $this->error('Cannot reset reconciled payment', 422);
        }

        // Reverse any partial reconciliation
        $this->unreconciledPayment($payment);

        $payment->update([
            'state' => 'draft',
        ]);

        $this->dispatchWebhookEvent($payment->fresh(), 'reset_to_draft');

        return $this->success(
            $this->transformModel($payment->fresh()),
            'Payment reset to draft'
        );
    }

    /**
     * Reconcile payment with invoices/bills
     */
    protected function reconcilePayment(Payment $payment, $moves, float $amount): void
    {
        $remainingAmount = $amount;

        foreach ($moves as $move) {
            if ($remainingAmount <= 0) {
                break;
            }

            $residual = $move->amount_residual ?? $move->amount_total ?? 0;
            $toApply = min($remainingAmount, $residual);

            $move->updateQuietly([
                'amount_residual' => $residual - $toApply,
                'payment_state' => ($residual - $toApply) <= 0 ? 'paid' : 'partial',
            ]);

            $remainingAmount -= $toApply;
        }

        // Mark payment as reconciled if fully applied
        $payment->updateQuietly([
            'is_reconciled' => $remainingAmount <= 0,
            'is_matched' => true,
        ]);
    }

    /**
     * Unreoncile payment (reverse reconciliation)
     */
    protected function unreconciledPayment(Payment $payment): void
    {
        $moves = $payment->accountMovePayment;

        foreach ($moves as $move) {
            // Restore the original residual (approximate - would need journal entries in full implementation)
            $move->updateQuietly([
                'payment_state' => 'not_paid',
            ]);
        }

        $payment->updateQuietly([
            'is_reconciled' => false,
            'is_matched' => false,
        ]);
    }
}
