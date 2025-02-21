<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\OrderTemplateProductResource\Pages;

use Webkul\Sale\Filament\Clusters\Configuration\Resources\OrderTemplateProductResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrderTemplateProduct extends EditRecord
{
    protected static string $resource = OrderTemplateProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('sales::filament/clusters/configurations/resources/order-template/pages/edit-order-template.notification.title'))
            ->body(__('sales::filament/clusters/configurations/resource/order-template/pages/edit-order-template.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-pencil-square'),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('sales::filament/clusters/configurations/resources/order-template/pages/edit-order-template.header-actions.notification.delete.title'))
                        ->body(__('sales::filament/clusters/configurations/resource/order-template/pages/edit-order-template.header-actions.notification.delete.body'))
                )
        ];
    }
}
