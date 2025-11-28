<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource;
use Webkul\Product\Filament\Resources\CategoryResource\Pages\CreateCategory;

/**
 * Create Product Category class
 *
 * @see \Filament\Resources\Resource
 */
class CreateProductCategory extends CreateCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
