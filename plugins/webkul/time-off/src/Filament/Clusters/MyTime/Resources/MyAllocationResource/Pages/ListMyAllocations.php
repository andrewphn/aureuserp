<?php

namespace Webkul\TimeOff\Filament\Clusters\MyTime\Resources\MyAllocationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\TimeOff\Filament\Clusters\MyTime\Resources\MyAllocationResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List My Allocations class
 *
 * @see \Filament\Resources\Resource
 */
class ListMyAllocations extends ListRecords
{
    use HasTableViews;

    protected static string $resource = MyAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('time-off::filament/clusters/my-time/resources/my-allocation/pages/list-my-allocations.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }
}
