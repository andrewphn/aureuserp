<?php

namespace Webkul\Employee\Filament\Clusters\Configurations\Resources\WorkLocationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\WorkLocationResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListWorkLocations extends ListRecords
{
    use HasTableViews;

    protected static string $resource = WorkLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('employees::filament/clusters/configurations/resources/work-location/pages/list-work-location.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/clusters/configurations/resources/work-location/pages/list-work-location.header-actions.create.notification.title'))
                        ->body(__('employees::filament/clusters/configurations/resources/work-location/pages/list-work-location.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('employees::filament/clusters/configurations/resources/work-location/pages/list-work-location.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
            'office' => PresetView::make(__('employees::filament/clusters/configurations/resources/work-location/pages/list-work-location.tabs.office'))
                ->icon('heroicon-m-building-office-2')
                ->modifyQueryUsing(fn ($query) => $query->where('location_type', 'office')),
            'home' => PresetView::make(__('employees::filament/clusters/configurations/resources/work-location/pages/list-work-location.tabs.home'))
                ->icon('heroicon-m-home')
                ->modifyQueryUsing(fn ($query) => $query->where('location_type', 'home')),
            'other' => PresetView::make(__('employees::filament/clusters/configurations/resources/work-location/pages/list-work-location.tabs.other'))
                ->icon('heroicon-m-map-pin')
                ->modifyQueryUsing(fn ($query) => $query->where('location_type', 'other')),
        ];
    }
}
