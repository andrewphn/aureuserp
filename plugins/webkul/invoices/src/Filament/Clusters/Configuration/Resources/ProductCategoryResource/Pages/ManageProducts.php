<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource;
use Webkul\Product\Filament\Resources\CategoryResource\Pages\ManageProducts as BaseManageProducts;

/**
 * Manage Products class
 *
 * @see \Filament\Resources\Resource
 */
class ManageProducts extends BaseManageProducts
{
    protected static string $resource = ProductCategoryResource::class;
}
