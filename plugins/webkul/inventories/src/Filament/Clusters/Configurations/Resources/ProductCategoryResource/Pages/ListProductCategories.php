<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource;
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
