<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums as AccountEnums;
use Webkul\Account\Filament\Resources\InvoiceResource;
use Webkul\Account\Models\Journal as AccountJournal;
use Webkul\Account\Models\Move;
use Webkul\Invoice\Enums\InvoicePolicy;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
use Webkul\Sale\Settings\InvoiceSettings;

class CreateInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.create-invoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color(function (Order $record): string {
                if ($record->qty_to_invoice == 0) {
                    return 'gray';
                }

                return 'primary';
            })
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.title'))
            ->hidden(fn ($record) => $record->invoice_status != InvoiceStatus::TO_INVOICE->value)
            ->action(function (Order $record, $livewire) {
                if ($record->qty_to_invoice == 0) {
                    Notification::make()
                        ->title(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.no-invoiceable-lines.title'))
                        ->body(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.no-invoiceable-lines.body'))
                        ->warning()
                        ->send();

                    return;
                }

                $this->createAccountMove($record);

                QuotationResource::collectTotals($record);

                Notification::make()
                    ->title(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.invoice-created.title'))
                    ->body(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.invoice-created.body'))
                    ->success()
                    ->send();

                $livewire->redirect(InvoiceResource::getUrl('edit', ['record' => $record]), navigate: FilamentView::hasSpaMode());
            });
    }

    private function createAccountMove(Order $record)
    {
        $accountMove = Move::create([
            'state'                        => AccountEnums\MoveState::DRAFT->value,
            'move_type'                    => AccountEnums\MoveType::OUT_INVOICE->value,
            'payment_state'                => AccountEnums\PaymentStatus::NOT_PAID->value,
            'invoice_partner_display_name' => $record->partner->name,
            'invoice_origin'               => $record->name,
            'date'                         => now(),
            'invoice_date_due'             => now(),
            'invoice_currency_rate'        => 1,
            'journal_id'                   => AccountJournal::where('type', AccountEnums\JournalType::SALE->value)->first()?->id,
            'company_id'                   => $record->company_id,
            'currency_id'                  => $record->currency_id,
            'invoice_payment_term_id'      => $record->payment_term_id,
            'partner_id'                   => $record->partner_id,
            'commercial_partner_id'        => $record->partner_id,
            'partner_shipping_id'          => $record->partner->addresses->where('type', 'present')->first()?->id,
            'fiscal_position_id'           => $record->fiscal_position_id,
            'creator_id'                   => Auth::id(),
        ]);

        $record->accountMoves()->attach($accountMove->id);

        foreach ($record->lines as $line) {
            $this->createAccountMoveLine($accountMove, $line);
        }

        InvoiceResource::collectTotals($accountMove);

        $record->update([
            'invoice_status' => InvoiceStatus::INVOICED->value,
        ]);
    }

    private function createAccountMoveLine(Move $accountMove, OrderLine $orderLine): void
    {
        $productInvoicePolicy = $orderLine->product?->invoice_policy;
        $invoiceSetting = app(InvoiceSettings::class)->invoice_policy;

        $quantity = ($productInvoicePolicy ?? $invoiceSetting) === InvoicePolicy::ORDER->value
            ? $orderLine->qty_to_invoice
            : $orderLine->product_uom_qty;

        $moveLineData = [
            'state'                  => AccountEnums\MoveState::DRAFT,
            'name'                   => $orderLine->name,
            'display_type'           => AccountEnums\DisplayType::PRODUCT,
            'date'                   => $accountMove->date,
            'creator_id'             => $accountMove?->creator_id,
            'parent_state'           => $accountMove->state,
            'quantity'               => $quantity,
            'price_unit'             => $orderLine->price_unit,
            'discount'               => $orderLine->discount,
            'journal_id'             => $accountMove->journal_id,
            'company_id'             => $accountMove->company_id,
            'currency_id'            => $accountMove->currency_id,
            'company_currency_id'    => $accountMove->currency_id,
            'partner_id'             => $accountMove->partner_id,
            'product_id'             => $orderLine->product_id,
            'uom_id'                 => $orderLine->uom_id,
            'purchase_order_line_id' => $orderLine->id,
            'debit'                  => $orderLine?->price_subtotal,
            'credit'                 => 0.00,
            'balance'                => $orderLine?->price_subtotal,
            'amount_currency'        => $orderLine?->price_subtotal,
        ];

        $accountMoveLine = $accountMove->lines()->create($moveLineData);

        $orderLine->qty_invoiced += $orderLine->qty_to_invoice;

        $orderLine->save();

        $accountMoveLine->taxes()->sync($orderLine->taxes->pluck('id'));
    }
}
