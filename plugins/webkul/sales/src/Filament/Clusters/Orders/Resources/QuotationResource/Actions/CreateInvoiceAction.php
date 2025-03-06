<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums as AccountEnums;
use Webkul\Account\Filament\Resources\InvoiceResource;
use Webkul\Account\Models\Journal as AccountJournal;
use Webkul\Account\Models\Move;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Sale\Models\Order;

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
            ->label(__('sales::traits/sale-order-action.header-actions.create-invoice.title'))
            ->hidden(fn ($record) => $record->invoice_status != InvoiceStatus::TO_INVOICE->value)
            ->action(function (Order $record) {
                if ($record->qty_to_invoice == 0) {
                    Notification::make()
                        ->title(__('No invoiceable lines'))
                        ->body(__('There is no invoiceable line, please make sure that a quantity has been received.'))
                        ->warning()
                        ->send();

                    return;
                }

                $this->createAccountMove($record);

                QuotationResource::collectTotals($record);

                Notification::make()
                    ->title(__('Invoice Created'))
                    ->body(__('Invoice has been created successfully.'))
                    ->success()
                    ->send();
            });
    }

    private function createAccountMove($record)
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
    }

    private function createAccountMoveLine($accountMove, $orderLine): void
    {
        $accountMoveLine = $accountMove->lines()->create([
            'state'                  => AccountEnums\MoveState::DRAFT,
            'name'                   => $orderLine->name,
            'display_type'           => AccountEnums\DisplayType::PRODUCT,
            'date'                   => $accountMove->date,
            'creator_id'             => $accountMove?->creator_id,
            'parent_state'           => $accountMove->state,
            'quantity'               => $orderLine->qty_to_invoice,
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
        ]);

        $orderLine->qty_invoiced += $orderLine->qty_to_invoice;

        $orderLine->save();

        $accountMoveLine->taxes()->sync($orderLine->taxes->pluck('id'));
    }
}
