<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\JournalResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\JournalResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewJournal extends ViewRecord
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/clusters/configurations/resources/journal/pages/view-journal.notification.title'))
                        ->body(__('accounts::filament/clusters/configurations/resources/journal/pages/view-journal.notification.body'))
                ),
        ];
    }
}
