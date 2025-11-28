<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ViewProduct as BaseViewProduct;

/**
 * View Product class
 *
 * @see \Filament\Resources\Resource
 */
class ViewProduct extends BaseViewProduct
{
    protected static string $resource = ProductResource::class;
}
