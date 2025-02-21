<?php

namespace Webkul\Account\Filament\Resources\ProductCategoryResource\Pages;

use Webkul\Account\Filament\Resources\ProductCategoryResource;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\CreateProductCategory as BaseCreateProductCategory;

class CreateProductCategory extends BaseCreateProductCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
