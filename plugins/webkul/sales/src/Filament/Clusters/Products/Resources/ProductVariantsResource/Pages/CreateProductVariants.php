<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\CreateProduct as BaseCreateProduct;

class CreateProductVariants extends BaseCreateProduct
{
    protected static string $resource = ProductVariantsResource::class;
}
