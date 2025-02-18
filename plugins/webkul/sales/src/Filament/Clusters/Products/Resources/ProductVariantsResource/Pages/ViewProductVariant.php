<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\ViewProduct as BaseViewProduct;

class ViewProductVariant extends BaseViewProduct
{
    protected static string $resource = ProductVariantsResource::class;
}
