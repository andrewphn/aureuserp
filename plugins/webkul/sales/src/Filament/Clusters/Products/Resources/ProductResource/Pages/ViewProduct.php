<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Filament\Pages\Enums\SubNavigationPosition;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ViewProduct as BaseViewProduct;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource;

/**
 * View Product class
 *
 * @see \Filament\Resources\Resource
 */
class ViewProduct extends BaseViewProduct
{
    protected static string $resource = ProductResource::class;

    /**
     * Get the sub-navigation position
     *
     * @return \Filament\Pages\Enums\SubNavigationPosition
     */
    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }
}
