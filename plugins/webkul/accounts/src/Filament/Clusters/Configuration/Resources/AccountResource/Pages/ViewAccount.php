<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Account\Filament\Clusters\Configuration\Resources\AccountResource;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/clusters/configurations/resources/account/pages/view-account.header-actions.delete.notification.title'))
                        ->body(__('accounts::filament/clusters/configurations/resources/account/pages/view-account.header-actions.delete.notification.body'))
                ),
        ];
    }
}
