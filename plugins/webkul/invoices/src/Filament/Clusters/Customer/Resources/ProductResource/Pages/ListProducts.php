<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ListProducts as BaseListProducts;

/**
 * List Products class
 *
 * @see \Filament\Resources\Resource
 */
class ListProducts extends BaseListProducts
{
    protected static string $resource = ProductResource::class;
}
