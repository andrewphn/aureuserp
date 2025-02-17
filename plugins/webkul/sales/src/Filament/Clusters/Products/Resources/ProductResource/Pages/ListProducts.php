<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\TableViews\Filament\Concerns\HasTableViews;
use Illuminate\Database\Eloquent\Builder;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ListProducts as BaseListProducts;

class ListProducts extends BaseListProducts
{
    use HasTableViews;

    protected static string $resource = ProductResource::class;

    public function getPresetTableViews(): array
    {
        $presets = [
            'goods_products' => PresetView::make(__('sales::filament/clusters/products/resources/product/pages/list-products.tabs.goods'))
                ->icon('heroicon-s-squares-plus')
                ->favorite()
                ->default()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', ProductType::GOODS)),

            'services_products' => PresetView::make(__('sales::filament/clusters/products/resources/product/pages/list-products.tabs.services'))
                ->icon('heroicon-s-sparkles')
                ->favorite()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', ProductType::SERVICE)),

            'storable_products' => PresetView::make(__('sales::filament/clusters/products/resources/product/pages/list-products.tabs.inventory-management'))
                ->icon('heroicon-s-clipboard-document-list')
                ->favorite()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_storable', true)),

            'favorites_products' => PresetView::make(__('sales::filament/clusters/products/resources/product/pages/list-products.tabs.favorites'))
                ->icon('heroicon-s-star')
                ->favorite()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_favorite', true)),

            'archived_products' => PresetView::make(__('sales::filament/clusters/products/resources/product/pages/list-products.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];

        return [
            ...$presets,
            ...parent::getPresetTableViews(),
        ];
    }
}
