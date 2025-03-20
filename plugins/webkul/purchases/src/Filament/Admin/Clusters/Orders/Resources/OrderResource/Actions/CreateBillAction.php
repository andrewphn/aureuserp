<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Webkul\Account\Enums as AccountEnums;
use Webkul\Account\Models\Journal as AccountJournal;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource;
use Webkul\Purchase\Models\AccountMove;
use Webkul\Purchase\Models\Order;

class CreateBillAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.create-bill';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.label'))
            ->color(function (Order $record): string {
                if ($record->qty_to_invoice == 0) {
                    return 'gray';
                }

                return 'primary';
            })
            ->action(function (Order $record, Component $livewire): void {
                if ($record->qty_to_invoice == 0) {
                    Notification::make()
                        ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.action.notification.warning.title'))
                        ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.action.notification.warning.body'))
                        ->warning()
                        ->send();

                    return;
                }

                $this->createAccountMove($record);

                OrderResource::collectTotals($record);

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.action.notification.success.title'))
                    ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/create-bill.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->visible(fn () => in_array($this->getRecord()->state, [
                OrderState::PURCHASE,
                OrderState::DONE,
            ]));
    }

    private function createAccountMove($record): void
    {
        $accountMove = AccountMove::create([
            'state'                        => AccountEnums\MoveState::DRAFT,
            'move_type'                    => $record->qty_to_invoice >=0 ? AccountEnums\MoveType::IN_INVOICE : AccountEnums\MoveType::OUT_INVOICE,
            'payment_state'                => AccountEnums\PaymentStatus::NOT_PAID,
            'invoice_partner_display_name' => $record->partner->name,
            'invoice_origin'               => $record->name,
            'date'                         => now(),
            'invoice_date_due'             => now(),
            'invoice_currency_rate'        => 1,
            'journal_id'                   => AccountJournal::where('type', AccountEnums\JournalType::PURCHASE->value)->first()?->id,
            'company_id'                   => $record->company_id,
            'currency_id'                  => $record->currency_id,
            'invoice_payment_term_id'      => $record->payment_term_id,
            'partner_id'                   => $record->partner_id,
            'commercial_partner_id'        => $record->partner_id,
            'partner_shipping_id'          => $record->partner_shipping_id,
            // 'partner_bank_id' => $record->partner_bank_id,//TODO: add partner bank id
            'fiscal_position_id' => $record->fiscal_position_id,
            // 'preferred_payment_method_line_id' => 1,
            'creator_id' => Auth::id(),
        ]);

        $record->accountMoves()->attach($accountMove->id);

        foreach ($record->lines as $line) {
            $this->createAccountMoveLine($accountMove, $line);
        }

        if ($record->qty_to_invoice >= 0) {
            BillResource::collectTotals($accountMove);
        } else {
            RefundResource::collectTotals($accountMove);
        }
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
            'quantity'               => abs($orderLine->qty_to_invoice),
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
        ]);

        $orderLine->qty_invoiced += $orderLine->qty_to_invoice;

        $orderLine->save();

        $accountMoveLine->taxes()->sync($orderLine->taxes->pluck('id'));
    }
}
