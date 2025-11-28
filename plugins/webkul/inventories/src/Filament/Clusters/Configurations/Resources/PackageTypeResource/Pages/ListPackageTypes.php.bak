<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackageTypeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackageTypeResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListPackageTypes extends ListRecords
{
    use HasTableViews;

    protected static string $resource = PackageTypeResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('inventories::filament/clusters/configurations/resources/package-type/pages/list-package-types.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('inventories::filament/clusters/configurations/resources/package-type/pages/list-package-types.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['creator_id'] = $user->id;

                    $data['company_id'] = $user->default_company_id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/configurations/resources/package-type/pages/list-package-types.header-actions.create.notification.title'))
                        ->body(__('inventories::filament/clusters/configurations/resources/package-type/pages/list-package-types.header-actions.create.notification.body')),
                ),
        ];
    }
}
