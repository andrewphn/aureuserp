<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Facades\SaleOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;

/**
 * Confirm Action Filament action
 *
 * @see \Filament\Resources\Resource
 */
class ConfirmAction extends Action
{
    /**
     * Get the default name for this action
     *
     * @return string|null Action identifier
     */
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.confirm';
    }

    /**
     * Configure the action to confirm a quotation and convert it to a sale order
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color(fn(): string => $this->getRecord()->state === OrderState::DRAFT ? 'gray' : 'primary')
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.title'))
            ->hidden(fn($record) => $record->state == OrderState::SALE)
            ->action(function ($record, $livewire) {
                try {
                    $record = SaleOrder::confirmSaleOrder($record);
                } catch (Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.error.title'))
                        ->body($e->getMessage())
                        ->send();

                    return;
                }

                $livewire->refreshFormData(['state']);

                $livewire->redirect(OrderResource::getUrl('edit', ['record' => $record]), navigate: FilamentView::hasSpaMode());

                Notification::make()
                    ->success()
                    ->title(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.confirmed.title'))
                    ->body(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.confirmed.body'))
                    ->send();
            });
    }
}
