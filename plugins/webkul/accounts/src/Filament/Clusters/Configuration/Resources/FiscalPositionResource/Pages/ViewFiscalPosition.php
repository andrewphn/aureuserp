<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\ViewRecord;

class ViewFiscalPosition extends ViewRecord
{
    protected static string $resource = FiscalPositionResource::class;

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
                        ->title(__('accounts::filament/clusters/configurations/resources/fiscal-position/pages/view-fiscal-position.header-actions.delete.notification.title'))
                        ->body(__('accounts::filament/clusters/configurations/resources/fiscal-position/pages/view-fiscal-position.header-actions.delete.notification.body'))
                ),
        ];
    }
}
