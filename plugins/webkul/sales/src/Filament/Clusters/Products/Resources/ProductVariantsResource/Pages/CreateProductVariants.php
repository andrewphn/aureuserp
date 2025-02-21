<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\CreateProduct as BaseCreateProduct;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource;

class CreateProductVariants extends BaseCreateProduct
{
    protected static string $resource = ProductVariantsResource::class;
}
