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
            ->label(__('Back To Quotation'))
            ->color('gray')
            ->hidden(fn ($record) => $record->state != OrderState::CANCEL->value)
            ->action(function ($record, $livewire) {
                $record->update([
                    'state'          => OrderState::DRAFT->value,
                    'invoice_status' => InvoiceStatus::NO->value,
                ]);

                $livewire->refreshFormData(['state']);

                Notification::make()
                    ->success()
                    ->title(__('sales::traits/sale-order-action.header-actions.back-to-quotation.notification.back-to-quotation.title'))
                    ->body(__('sales::traits/sale-order-action.header-actions.back-to-quotation.notification.back-to-quotation.body'))
                    ->send();
            });
    }
}
