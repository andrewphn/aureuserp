<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrdersResource;
use Webkul\Sale\Settings\QuotationAndOrderSettings;

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
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.title'))
            ->hidden(fn ($record) => $record->state != OrderState::DRAFT)
            ->action(function ($record, $livewire, QuotationAndOrderSettings $settings) {
                $data = [
                    'state'          => OrderState::SALE,
                    'invoice_status' => InvoiceStatus::TO_INVOICE,
                ];

                if ($settings->enable_lock_confirm_sales) {
                    $data['locked'] = true;
                }

                $record->update($data);

                $record->lines->each(function ($line) {
                    $line->state = OrderState::SALE;
                    $line->save();
                });

                $livewire->refreshFormData(['state']);

                $livewire->redirect(OrdersResource::getUrl('edit', ['record' => $record]), navigate: FilamentView::hasSpaMode());

                Notification::make()
                    ->success()
                    ->title(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.confirmed.title'))
                    ->body(__('sales::filament/clusters/orders/resources/quotation/actions/confirm.notification.confirmed.body'))
                    ->send();
            });
    }
}
