<?php

namespace Webkul\Sale;

use Illuminate\Support\Facades\Auth;
use Webkul\Account\Facades\Tax;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Mail\SaleOrderCancelQuotation;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
use Webkul\Support\Services\EmailService;

class SaleManager
{
    public function confirmSaleOrder(Order $record): Order
    {
        $data = [
            'state'          => OrderState::SALE,
            'invoice_status' => InvoiceStatus::TO_INVOICE,
        ];

        if ($settings->enable_lock_confirm_sales) {
            $data['locked'] = true;
        }

        $record->update($data);
        $record = $this->computeSaleOrder($record);

        return $record;
    }

    /**
     * Confirm the sale order.
     */
    public function backToQuotation(Order $record): Order
    {
        $record->update([
            'state'          => OrderState::DRAFT,
            'invoice_status' => InvoiceStatus::NO,
        ]);

        $record = $this->computeSaleOrder($record);

        return $record;
    }

    /**
     * Cancel the sale order.
     */
    public function cancelSaleOrder(Order $record, array $data = []): Order
    {
        $record->update([
            'state'          => OrderState::CANCEL,
            'invoice_status' => InvoiceStatus::NO,
        ]);

        if (! empty($data)) {
            $this->cancelAndSendEmail($record, $data);
        }

        $record = $this->computeSaleOrder($record);

        return $record;
    }

    /**
     * Compute the sale order.
     */
    public static function computeSaleOrder(Order $record): Order
    {
        $record->amount_untaxed = 0;
        $record->amount_tax = 0;
        $record->amount_total = 0;

        foreach ($record->lines as $line) {
            $line->state = $record->state;

            $line = static::computeSaleOrderLine($line);

            $record->amount_untaxed += $line->price_subtotal;
            $record->amount_tax += $line->price_tax;
            $record->amount_total += $line->price_total;
        }

        $record->save();

        return $record;
    }

    /**
     * Compute the sale order line.
     */
    public static function computeSaleOrderLine(OrderLine $line): OrderLine
    {
        $qtyDelivered = $line->qty_delivered ?? 0;

        $line->qty_to_invoice = $qtyDelivered - $line->qty_invoiced;

        $subTotal = $line->price_unit * $line->product_qty;

        $discountAmount = 0;

        if ($line->discount > 0) {
            $discountAmount = $subTotal * ($line->discount / 100);

            $subTotal = $subTotal - $discountAmount;
        }

        $taxIds = $line->taxes->pluck('id')->toArray();

        [$subTotal, $taxAmount] = Tax::collect($taxIds, $subTotal, $line->product_qty);

        $line->price_subtotal = round($subTotal, 4);

        $line->price_tax = $taxAmount;

        $line->price_total = $subTotal + $taxAmount;

        $line->sort = $line->sort ?? OrderLine::max('sort') + 1;

        $line->technical_price_unit = $line->price_unit;

        $line->price_reduce_taxexcl = $line->price_unit - ($line->price_unit * ($line->discount / 100));

        $line->price_reduce_taxinc = round($line->price_reduce_taxexcl + ($line->price_reduce_taxexcl * ($line->taxes->sum('amount') / 100)), 2);

        $line->state = $line->order->state;

        $line->save();

        return $line;
    }

    /**
     * Handle cancel and send email.
     *
     * @return void
     */
    public function cancelAndSendEmail(Order $record, array $data)
    {
        $partners = Partner::whereIn('id', $data['partners'])->get();

        foreach ($partners as $partner) {
            $payload = [
                'record_name'    => $record->name,
                'model_name'     => 'Quotation',
                'subject'        => $data['subject'],
                'description'    => $data['description'],
                'to'             => [
                    'address' => $partner?->email,
                    'name'    => $partner?->name,
                ],
            ];

            app(EmailService::class)->send(
                mailClass: SaleOrderCancelQuotation::class,
                view: $viewName = 'sales::mails.sale-order-cancel-quotation',
                payload: $payload,
            );

            $record->addMessage([
                'from' => [
                    'company' => Auth::user()->defaultCompany->toArray(),
                ],
                'body' => view($viewName, [
                    'payload' => $payload,
                ])->render(),
                'type' => 'comment',
            ]);
        }
    }
}
