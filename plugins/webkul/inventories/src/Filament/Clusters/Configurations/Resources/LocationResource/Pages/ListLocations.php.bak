<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\LocationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\LocationResource;
use Webkul\Inventory\Models\Location;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListLocations extends ListRecords
{
    use HasTableViews;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.header-actions.create.label'))
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
                        ->title(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.header-actions.create.notification.title'))
                        ->body(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.tabs.all'))
                ->icon('heroicon-s-map-pin')
                ->favorite()
                ->badge(Location::count()),
            'internal' => PresetView::make(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.tabs.internal'))
                ->icon('heroicon-s-building-office-2')
                ->favorite()
                ->setAsDefault()
                ->badge(Location::where('type', LocationType::INTERNAL)->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('type', LocationType::INTERNAL);
                }),
            'customer' => PresetView::make(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.tabs.customer'))
                ->icon('heroicon-s-user-group')
                ->favorite()
                ->badge(Location::where('type', LocationType::CUSTOMER)->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('type', LocationType::CUSTOMER);
                }),
            'production' => PresetView::make(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.tabs.production'))
                ->icon('heroicon-s-cog-6-tooth')
                ->favorite()
                ->badge(Location::where('type', LocationType::PRODUCTION)->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('type', LocationType::PRODUCTION);
                }),
            'vendor' => PresetView::make(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.tabs.vendor'))
                ->icon('heroicon-s-truck')
                ->favorite()
                ->badge(Location::where('type', LocationType::SUPPLIER)->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->where('type', LocationType::SUPPLIER);
                }),
            'archived' => PresetView::make(__('inventories::filament/clusters/configurations/resources/location/pages/list-locations.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->badge(Location::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }
}
