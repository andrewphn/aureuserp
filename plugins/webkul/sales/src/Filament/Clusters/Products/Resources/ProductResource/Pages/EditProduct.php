<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Filament\Pages\SubNavigationPosition;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Product\Filament\Resources\ProductResource\Pages\EditProduct as BaseEditProduct;

class EditProduct extends BaseEditProduct
{
    protected static string $resource = ProductResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }
}
