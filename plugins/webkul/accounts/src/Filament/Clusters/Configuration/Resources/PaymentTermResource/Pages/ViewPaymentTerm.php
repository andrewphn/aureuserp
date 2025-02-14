<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\PaymentTermResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\PaymentTermResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentTerm extends ViewRecord
{
    protected static string $resource = PaymentTermResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/clusters/configurations/resources/payment-term/pages/view-payment-term.header-actions.delete.notification.title'))
                        ->body(__('accounts::filament/clusters/configurations/resources/payment-term/pages/view-payment-term.header-actions.delete.notification.body'))
                ),
        ];
    }
}
