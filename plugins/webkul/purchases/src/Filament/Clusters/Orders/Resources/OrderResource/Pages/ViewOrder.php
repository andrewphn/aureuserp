<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Purchase\Enums;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

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
                        ->title(__('inventories::filament/clusters/orders/resources/order/pages/view-order.header-actions.delete.notification.title'))
                        ->body(__('inventories::filament/clusters/orders/resources/order/pages/view-order.header-actions.delete.notification.body')),
                ),
        ];
    }
}
