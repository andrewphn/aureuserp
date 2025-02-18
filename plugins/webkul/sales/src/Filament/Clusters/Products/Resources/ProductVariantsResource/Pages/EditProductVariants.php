<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\EditProduct as BaseEditProduct;

class EditProductVariants extends BaseEditProduct
{
    protected static string $resource = ProductVariantsResource::class;
}
