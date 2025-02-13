<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Product\Filament\Resources\CategoryResource\Pages\ManageProducts as BaseManageProducts;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource;

class ManageProducts extends BaseManageProducts
{
    protected static string $resource = ProductCategoryResource::class;
}
