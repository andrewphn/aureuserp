<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\StorageCategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\StorageCategoryResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Storage Categories class
 *
 * @see \Filament\Resources\Resource
 */
class ListStorageCategories extends ListRecords
{
    use HasTableViews;

    protected static string $resource = StorageCategoryResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('inventories::filament/clusters/configurations/resources/storage-category/pages/list-storage-categories.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('inventories::filament/clusters/configurations/resources/storage-category/pages/list-storage-categories.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['creator_id'] = $user->id;

                    $data['company_id'] = $user->defaultCompany?->id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/configurations/resources/storage-category/pages/list-storage-categories.header-actions.create.notification.title'))
                        ->body(__('inventories::filament/clusters/configurations/resources/storage-category/pages/list-storage-categories.header-actions.create.notification.body')),
                )
                ->successRedirectUrl(fn ($record) => StorageCategoryResource::getUrl('edit', ['record' => $record])),
        ];
    }
}
