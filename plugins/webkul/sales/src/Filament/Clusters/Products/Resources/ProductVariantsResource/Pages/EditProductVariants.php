<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\EditProduct as BaseEditProduct;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource;

class EditProductVariants extends BaseEditProduct
{
    protected static string $resource = ProductVariantsResource::class;
}
