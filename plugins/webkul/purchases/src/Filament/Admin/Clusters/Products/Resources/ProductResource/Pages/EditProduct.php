<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Product\Filament\Resources\ProductResource\Pages\EditProduct as BaseEditProduct;
use Webkul\Purchase\Filament\Admin\Clusters\Products\Resources\ProductResource;

/**
 * Edit Product class
 *
 * @see \Filament\Resources\Resource
 */
class EditProduct extends BaseEditProduct
{
    protected static string $resource = ProductResource::class;
}
