<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Facades\SaleOrder;

/**
 * Back To Quotation Action Filament action
 *
 * @see \Filament\Resources\Resource
 */
class BackToQuotationAction extends Action
{
    /**
     * Get the default name for this action
     *
     * @return string|null Action identifier
     */
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.bak-to-quotation';
    }

    /**
     * Configure the action to revert a cancelled order back to quotation status
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/back-to-quotation.title'))
            ->color('gray')
            ->hidden(fn ($record) => $record->state != OrderState::CANCEL)
            ->action(function ($record, $livewire) {
                SaleOrder::backToQuotation($record);

                $livewire->refreshFormData(['state']);

                Notification::make()
                    ->success()
                    ->title(__('sales::filament/clusters/orders/resources/quotation/actions/back-to-quotation.notification.back-to-quotation.title'))
                    ->body(__('sales::filament/clusters/orders/resources/quotation/actions/back-to-quotation.notification.back-to-quotation.body'))
                    ->send();
            });
    }
}
