<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Livewire\Component;
use Webkul\Purchase\Enums\OrderState;
use Filament\Notifications\Notification;
use Webkul\Purchase\Models\Order;

class CancelAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('purchases::filament/clusters/orders/resources/order/actions/cancel.label'))
            ->color('gray')
            ->action(function (Order $record, Component $livewire): void {
                $record->update([
                    'state' => OrderState::CANCELED,
                ]);

                foreach ($record->lines as $move) {
                    $move->update([
                        'state' => OrderState::CANCELED,
                    ]);
                }

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/clusters/orders/resources/order/actions/cancel.action.notification.success.title'))
                    ->body(__('purchases::filament/clusters/orders/resources/order/actions/cancel.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->visible(fn () => ! in_array($this->getRecord()->state, [
                OrderState::DONE,
                OrderState::CANCELED,
            ]));
    }
}
