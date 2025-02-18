<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\ListProducts as BaseListProducts;

class ListProductVariants extends BaseListProducts
{
    protected static string $resource = ProductVariantsResource::class;
}
