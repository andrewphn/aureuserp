<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\OperationTypeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\OperationTypeResource;
use Webkul\Inventory\Models\OperationType;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Operation Types class
 *
 * @see \Filament\Resources\Resource
 */
class ListOperationTypes extends ListRecords
{
    use HasTableViews;

    protected static string $resource = OperationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('inventories::filament/clusters/configurations/resources/operation-type/pages/list-operation-types.header-actions.create.label'))
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
                        ->title(__('inventories::filament/clusters/configurations/resources/operation-type/pages/list-operation-types.header-actions.create.notification.title'))
                        ->body(__('inventories::filament/clusters/configurations/resources/operation-type/pages/list-operation-types.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('inventories::filament/clusters/configurations/resources/operation-type/pages/list-operation-types.tabs.all'))
                ->icon('heroicon-s-cog-6-tooth')
                ->favorite()
                ->setAsDefault()
                ->badge(OperationType::count()),
            'archived' => PresetView::make(__('inventories::filament/clusters/configurations/resources/operation-type/pages/list-operation-types.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->badge(OperationType::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }
}
