<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\WarehouseResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\WarehouseResource;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Settings\WarehouseSettings;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListWarehouses extends ListRecords
{
    use HasTableViews;

    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('inventories::filament/clusters/configurations/resources/warehouse/pages/list-warehouses.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->visible(WarehouseResource::getWarehouseSettings()->enable_locations)
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['creator_id'] = $user->id;

                    $data['company_id'] = $user->defaultCompany?->id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/configurations/resources/warehouse/pages/list-warehouses.header-actions.create.notification.title'))
                        ->body(__('inventories::filament/clusters/configurations/resources/warehouse/pages/list-warehouses.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('inventories::filament/clusters/configurations/resources/warehouse/pages/list-warehouses.tabs.all'))
                ->icon('heroicon-s-building-office-2')
                ->favorite()
                ->setAsDefault()
                ->badge(Warehouse::count()),
            'archived' => PresetView::make(__('inventories::filament/clusters/configurations/resources/warehouse/pages/list-warehouses.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->badge(Warehouse::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }
}
