<?php

namespace Webkul\Account\Filament\Resources\IncoTermResource\Pages;

use Webkul\Account\Filament\Resources\IncoTermResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListIncoTerms extends ListRecords
{
    protected static string $resource = IncoTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/clusters/configurations/resources/inco-term/pages/list-inco-term.header-actions.notification.title'))
                        ->body(__('accounts::filament/clusters/configurations/resources/inco-term/pages/list-inco-term.header-actions.notification.body'))
                )
        ];
    }
}
