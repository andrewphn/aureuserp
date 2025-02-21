<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Sale\Traits\HasSaleOrderActions;
use Filament\Actions;
use Filament\Notifications\Notification;

class ViewQuotation extends ViewRecord
{
    use HasSaleOrderActions;

    protected static string $resource = QuotationResource::class;

    protected function getAdditionalHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('sales::filament/clusters/orders/resources/quotation/pages/view-quotation.header-actions.notification.delete.title'))
                        ->body(__('sales::filament/clusters/orders/resources/quotation/pages/view-quotation.header-actions.notification.delete.body'))
                )
        ];
    }
}
