<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Product\Filament\Resources\CategoryResource\Pages\ListCategories;
use Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\ProductCategoryResource;

/**
 * List Product Categories class
 *
 * @see \Filament\Resources\Resource
 */
class ListProductCategories extends ListCategories
{
    protected static string $resource = ProductCategoryResource::class;
}
