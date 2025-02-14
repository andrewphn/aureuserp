<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/clusters/configurations/resources/account/pages/create-account.notification.title'))
            ->body(__('accounts::filament/clusters/configurations/resources/account/pages/create-account.notification.body'));
    }
}
