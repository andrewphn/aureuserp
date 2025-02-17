<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\ListProducts as BaseListProducts;
use Webkul\TableViews\Filament\Components\PresetView;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends BaseListProducts
{
    protected static string $resource = ProductResource::class;

    public function getPresetTableViews(): array
    {
        $predefinedPresets = [
            'sales' => PresetView::make(__('Sales'))
                ->icon('heroicon-s-scale')
                ->favorite()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('sales_ok', true)),
            'purchase' => PresetView::make(__('Purchase'))
                ->icon('heroicon-s-arrow-top-right-on-square')
                ->favorite()
                ->default()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('purchase_ok', true)),
        ];

        return [
            ...$predefinedPresets,
            ...parent::getPresetTableViews(),
        ];
    }
}
