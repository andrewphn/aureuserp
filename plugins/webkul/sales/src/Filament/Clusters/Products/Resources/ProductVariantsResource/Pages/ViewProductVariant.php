<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\ViewProduct as BaseViewProduct;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource;

class ViewProductVariant extends BaseViewProduct
{
    protected static string $resource = ProductVariantsResource::class;
}
