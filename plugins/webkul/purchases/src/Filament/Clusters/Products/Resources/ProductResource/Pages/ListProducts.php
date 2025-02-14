<?php

namespace Webkul\Purchase\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Purchase\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ListProducts as BaseListProducts;

class ListProducts extends BaseListProducts
{
    protected static string $resource = ProductResource::class;
}
