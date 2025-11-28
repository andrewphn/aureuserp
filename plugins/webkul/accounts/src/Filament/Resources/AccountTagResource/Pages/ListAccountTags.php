<?php

namespace Webkul\Account\Filament\Resources\AccountTagResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Filament\Resources\AccountTagResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Account Tags class
 *
 * @see \Filament\Resources\Resource
 */
class ListAccountTags extends ListRecords
{
    use HasTableViews;

    protected static string $resource = AccountTagResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/account-tag/pages/list-account-tags.tabs.all'))
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
                ->mutateDataUsing(function (array $data): array {
                    $data['creator_id'] = Auth::user()->id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/resources/account-tag/pages/list-account-tag.header-actions.notification.title'))
                        ->body(__('accounts::filament/resources/account-tag/pages/list-account-tag.header-actions.notification.body'))
                ),
        ];
    }
}
