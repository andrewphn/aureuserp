<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrdersResource;

class ConfirmAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color('primary')
            ->label(__('sales::traits/sale-order-action.header-actions.confirm.title'))
            ->hidden(fn ($record) => $record->state != OrderState::DRAFT->value)
            ->action(function ($record, $livewire) {
                $record->update([
                    'state'          => OrderState::SALE->value,
                    'invoice_status' => InvoiceStatus::TO_INVOICE->value,
                ]);

                $livewire->refreshFormData(['state']);

                $livewire->redirect(OrdersResource::getUrl('view', ['record' => $record]), navigate: FilamentView::hasSpaMode());

                Notification::make()
                    ->success()
                    ->title(__('sales::traits/sale-order-action.header-actions.confirm.notification.confirmed.title'))
                    ->body(__('sales::traits/sale-order-action.header-actions.confirm.notification.confirmed.body'))
                    ->send();
            });
    }
}
