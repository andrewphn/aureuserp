<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\TaxGroupResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\TaxGroupResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxGroup extends CreateRecord
{
    protected static string $resource = TaxGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/clusters/configurations/resources/tax-group/pages/create-tax-group.notification.title'))
            ->body(__('accounts::filament/clusters/configurations/resources/tax-group/pages/create-tax-group.notification.body'));
    }
}
