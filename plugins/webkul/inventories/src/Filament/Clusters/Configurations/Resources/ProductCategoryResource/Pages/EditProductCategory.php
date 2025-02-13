<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Product\Filament\Resources\CategoryResource\Pages\EditCategory;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource;

class EditProductCategory extends EditCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
