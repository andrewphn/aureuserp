<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Enums\OrderState;

class BackToQuotationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.bak-to-quotation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/back-to-quotation.title'))
            ->color('gray')
            ->hidden(fn ($record) => $record->state != OrderState::CANCEL)
            ->action(function ($record, $livewire) {
                $record->update([
                    'state'          => OrderState::DRAFT,
                    'invoice_status' => InvoiceStatus::NO,
                ]);

                $record->lines->each(function ($line) {
                    $line->state = OrderState::DRAFT;
                    $line->save();
                });

                $livewire->refreshFormData(['state']);

                Notification::make()
                    ->success()
                    ->title(__('sales::filament/clusters/orders/resources/quotation/actions/back-to-quotation.notification.back-to-quotation.title'))
                    ->body(__('sales::filament/clusters/orders/resources/quotation/actions/back-to-quotation.notification.back-to-quotation.body'))
                    ->send();
            });
    }
}
