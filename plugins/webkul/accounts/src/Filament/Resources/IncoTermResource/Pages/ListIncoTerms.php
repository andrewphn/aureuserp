<?php

namespace Webkul\Account\Filament\Resources\IncoTermResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Account\Filament\Resources\IncoTermResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Inco Terms class
 *
 * @see \Filament\Resources\Resource
 */
class ListIncoTerms extends ListRecords
{
    use HasTableViews;

    protected static string $resource = IncoTermResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/inco-term/pages/list-inco-terms.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/resources/inco-term/pages/list-inco-term.header-actions.notification.title'))
                        ->body(__('accounts::filament/resources/inco-term/pages/list-inco-term.header-actions.notification.body'))
                ),
        ];
    }
}
