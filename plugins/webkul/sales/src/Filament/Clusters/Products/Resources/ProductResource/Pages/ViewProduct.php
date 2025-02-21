<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource;
use Filament\Pages\SubNavigationPosition;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ViewProduct as BaseViewProduct;

class ViewProduct extends BaseViewProduct
{
    protected static string $resource = ProductResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }
}
