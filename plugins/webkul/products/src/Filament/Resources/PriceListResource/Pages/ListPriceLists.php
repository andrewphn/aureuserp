<?php

namespace Webkul\Product\Filament\Resources\PriceListResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Product\Filament\Resources\PriceListResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListPriceLists extends ListRecords
{
    use HasTableViews;

    protected static string $resource = PriceListResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('products::filament/resources/price-list/pages/list-price-lists.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
