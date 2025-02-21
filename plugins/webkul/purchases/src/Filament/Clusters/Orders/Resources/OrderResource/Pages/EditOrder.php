<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Purchase\Enums;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('purchases::filament/clusters/orders/resources/order/pages/edit-order.notification.title'))
            ->body(__('purchases::filament/clusters/orders/resources/order/pages/edit-order.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->state == Enums\OrderState::DONE)
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('purchases::filament/clusters/orders/resources/order/pages/edit-order.header-actions.delete.notification.title'))
                        ->body(__('purchases::filament/clusters/orders/resources/order/pages/edit-order.header-actions.delete.notification.body')),
                ),
        ];
    }
}
