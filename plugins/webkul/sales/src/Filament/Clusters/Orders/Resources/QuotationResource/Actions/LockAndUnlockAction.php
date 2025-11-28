<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Webkul\Sale\Facades\SaleOrder;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Settings\QuotationAndOrderSettings;

/**
 * Lock And Unlock Action Filament action
 *
 * @see \Filament\Resources\Resource
 */
class LockAndUnlockAction extends Action
{
    /**
     * Get the default name for this action
     *
     * @return string|null Action identifier
     */
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.lock';
    }

    /**
     * Configure the action to toggle order lock state
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn ($record) => $record->locked ? __('sales::filament/clusters/orders/resources/quotation/actions/lock-and-unlock.unlock') : __('sales::filament/clusters/orders/resources/quotation/actions/lock-and-unlock.lock'))
            ->color(fn ($record) => $record->locked ? 'primary' : 'gray')
            ->icon(fn ($record) => ! $record->locked ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
            ->action(function (Order $record): void {
                SaleOrder::lockAndUnlock($record);
            })
            ->visible(fn (QuotationAndOrderSettings $quotationAndOrderSettings) => $quotationAndOrderSettings?->enable_lock_confirm_sales);
    }
}
