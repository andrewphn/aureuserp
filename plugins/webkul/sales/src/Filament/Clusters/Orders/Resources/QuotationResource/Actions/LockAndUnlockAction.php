<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Settings\QuotationAndOrderSettings;

class LockAndUnlockAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.lock';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn ($record) => $record->locked ? __('Unlock') : __('Lock'))
            ->color(fn ($record) => $record->locked ? 'primary' : 'gray')
            ->icon(fn ($record) => ! $record->locked ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
            ->action(function (Order $record): void {
                $record->update(['locked' => ! $record->locked]);

                Notification::make()
                    ->title(__('purchases::filament/clusters/orders/resources/order/actions/lock.action.notification.success.title'))
                    ->body(__('purchases::filament/clusters/orders/resources/order/actions/lock.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->visible(fn (QuotationAndOrderSettings $quotationAndOrderSettings) => $quotationAndOrderSettings?->enable_lock_confirm_sales);
    }
}
