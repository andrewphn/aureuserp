<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource;
use Webkul\Product\Filament\Resources\CategoryResource\Pages\EditCategory;

/**
 * Edit Product Category class
 *
 * @see \Filament\Resources\Resource
 */
class EditProductCategory extends EditCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
