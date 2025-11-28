<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Product\Filament\Resources\ProductResource\Pages\ListProducts as BaseListProducts;
use Webkul\Purchase\Filament\Admin\Clusters\Products\Resources\ProductResource;

/**
 * List Products class
 *
 * @see \Filament\Resources\Resource
 */
class ListProducts extends BaseListProducts
{
    protected static string $resource = ProductResource::class;
}
