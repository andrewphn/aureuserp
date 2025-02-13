<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Product\Filament\Resources\CategoryResource\Pages\CreateCategory;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource;

class CreateProductCategory extends CreateCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
