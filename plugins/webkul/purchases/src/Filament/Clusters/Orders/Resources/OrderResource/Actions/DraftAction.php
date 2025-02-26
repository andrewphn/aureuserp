<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Livewire\Component;
use Webkul\Purchase\Enums\OrderState;
use Filament\Notifications\Notification;
use Webkul\Purchase\Models\Order;

class DraftAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.draft';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('purchases::filament/clusters/orders/resources/order/actions/draft.label'))
            ->color('gray')
            ->action(function (Order $record, Component $livewire): void {
                $record->update([
                    'state' => OrderState::DRAFT,
                ]);

                foreach ($record->lines as $move) {
                    $move->update([
                        'state' => OrderState::DRAFT,
                    ]);
                }

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/clusters/orders/resources/order/actions/draft.action.notification.success.title'))
                    ->body(__('purchases::filament/clusters/orders/resources/order/actions/draft.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->visible(fn () => $this->getRecord()->state == OrderState::CANCELED);
    }
}
