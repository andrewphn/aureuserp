<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;
use Webkul\Account\Enums\MoveState;
use Webkul\Purchase\Enums\OrderState;
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
            ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (Order $record, Component $livewire): void {
                $record->accountMoves->each(function ($move) {
                    if ($move->state !== MoveState::CANCEL) {
                        Notification::make()
                            ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.action.notification.warning.title'))
                            ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.action.notification.warning.body'))
                            ->warning()
                            ->send();

                        return;
                    }
                });

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
                    ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.action.notification.success.title'))
                    ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->visible(fn () => ! in_array($this->getRecord()->state, [
                OrderState::DONE,
                OrderState::CANCELED,
            ]));
    }
}
