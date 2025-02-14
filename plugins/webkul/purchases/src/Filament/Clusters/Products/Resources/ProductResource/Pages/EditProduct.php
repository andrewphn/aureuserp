<?php

namespace Webkul\Purchase\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Purchase\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Product\Filament\Resources\ProductResource\Pages\EditProduct as BaseEditProduct;

class EditProduct extends BaseEditProduct
{
    protected static string $resource = ProductResource::class;
}
