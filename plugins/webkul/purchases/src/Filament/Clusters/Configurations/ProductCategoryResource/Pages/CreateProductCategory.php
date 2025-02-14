<?php

namespace Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductCategoryResource;
use Webkul\Product\Filament\Resources\CategoryResource\Pages\CreateCategory;

class CreateProductCategory extends CreateCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
