<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;
use Webkul\Account\Enums\MoveState;
use Webkul\Purchase\Enums\OrderState;
use Illuminate\Support\Facades\Schema;
use Webkul\Purchase\Models\Order;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Enums as InventoryEnums;

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
                $record->lines->each(function ($move) {
                    if ($move->qty_received > 0) {
                        Notification::make()
                            ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.action.notification.warning.receipts.title'))
                            ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.action.notification.warning.receipts.body'))
                            ->warning()
                            ->send();

                        return;
                    }
                });

                $record->accountMoves->each(function ($move) {
                    if ($move->state !== MoveState::CANCEL) {
                        Notification::make()
                            ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.action.notification.warning.bills.title'))
                            ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/cancel.action.notification.warning.bills.body'))
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

                $this->cancelInventoryOperations($record);

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

    protected function cancelInventoryOperations(Order $record): void
    {
        if (! Schema::hasTable('inventories_operations')) {
            return;
        }

        if ($record->operations->isEmpty()) {
            return;
        }

        $record->operations->each(function ($operation) {
            foreach ($operation->moves as $move) {
                $move->update([
                    'state'    => InventoryEnums\MoveState::CANCELED,
                    'quantity' => 0,
                ]);

                $move->lines()->delete();
            }

            OperationResource::updateOperationState($operation);
        });
    }
}
