<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\RouteResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\RouteResource;
use Webkul\Inventory\Models\Route;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListRoutes extends ListRecords
{
    use HasTableViews;

    protected static string $resource = RouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('inventories::filament/clusters/configurations/resources/route/pages/list-routes.header-actions.create.label'))
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
                        ->title(__('inventories::filament/clusters/configurations/resources/route/pages/list-routes.header-actions.create.notification.title'))
                        ->body(__('inventories::filament/clusters/configurations/resources/route/pages/list-routes.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('inventories::filament/clusters/configurations/resources/route/pages/list-routes.tabs.all'))
                ->icon('heroicon-s-arrows-right-left')
                ->favorite()
                ->setAsDefault()
                ->badge(Route::count()),
            'archived' => PresetView::make(__('inventories::filament/clusters/configurations/resources/route/pages/list-routes.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->badge(Route::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }
}
