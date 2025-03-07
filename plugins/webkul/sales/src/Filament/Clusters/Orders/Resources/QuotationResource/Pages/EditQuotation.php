<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Sale\Traits\HasSaleOrderActions;

class EditQuotation extends EditRecord
{
    use HasSaleOrderActions;

    protected static string $resource = QuotationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('sales::filament/clusters/orders/resources/quotation/pages/edit-quotation.notification.title'))
            ->body(__('sales::filament/clusters/orders/resources/quotation/pages/edit-quotation.notification.body'));
    }

    protected function getAdditionalHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('sales::filament/clusters/orders/resources/quotation/pages/edit-quotation.header-actions.notification.delete.title'))
                        ->body(__('sales::filament/clusters/orders/resources/quotation/pages/edit-quotation.header-actions.notification.delete.body'))
                ),
        ];
    }
}
