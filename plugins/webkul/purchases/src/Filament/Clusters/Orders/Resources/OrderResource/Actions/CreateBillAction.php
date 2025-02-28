<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Livewire\Component;
use Webkul\Purchase\Enums\OrderState;
use Filament\Notifications\Notification;
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
            ->label(__('purchases::filament/clusters/orders/resources/order/actions/create-bill.label'))
            ->color(function(Order $record): string {
                if ($record->qty_to_invoice == 0) {
                    return 'gray';
                }

                return 'primary';
            })
            ->action(function (Order $record, Component $livewire): void {
                if ($record->qty_to_invoice == 0) {
                    Notification::make()
                        ->title(__('purchases::filament/clusters/orders/resources/order/actions/create-bill.action.notification.warning.title'))
                        ->body(__('purchases::filament/clusters/orders/resources/order/actions/create-bill.action.notification.warning.body'))
                        ->warning()
                        ->send();

                    return;
                }

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/clusters/orders/resources/order/actions/create-bill.action.notification.success.title'))
                    ->body(__('purchases::filament/clusters/orders/resources/order/actions/create-bill.action.notification.success.body'))
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

    }

    private function createAccountMoveLine($record): void
    {
    }
}
