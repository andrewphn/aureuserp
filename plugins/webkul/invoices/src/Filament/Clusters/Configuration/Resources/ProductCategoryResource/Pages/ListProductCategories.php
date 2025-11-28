<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource;
use Webkul\Product\Filament\Resources\CategoryResource\Pages\ListCategories;

/**
 * List Product Categories class
 *
 * @see \Filament\Resources\Resource
 */
class ListProductCategories extends ListCategories
{
    protected static string $resource = ProductCategoryResource::class;
}
