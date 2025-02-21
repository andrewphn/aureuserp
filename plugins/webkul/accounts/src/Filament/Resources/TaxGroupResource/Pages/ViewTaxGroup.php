<?php

namespace Webkul\Account\Filament\Resources\TaxGroupResource\Pages;

use Webkul\Account\Filament\Resources\TaxGroupResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTaxGroup extends ViewRecord
{
    protected static string $resource = TaxGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/clusters/configurations/resources/tax-group/pages/view-tax-group.header-actions.delete.notification.title'))
                        ->body(__('accounts::filament/clusters/configurations/resources/tax-group/pages/view-tax-group.header-actions.delete.notification.body'))
                )
        ];
    }
}
