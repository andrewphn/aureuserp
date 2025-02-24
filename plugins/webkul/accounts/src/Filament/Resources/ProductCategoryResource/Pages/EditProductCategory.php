<?php

namespace Webkul\Account\Filament\Resources\ProductCategoryResource\Pages;

use Webkul\Account\Filament\Resources\ProductCategoryResource;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\EditProductCategory as BaseEditProductCategory;

class EditProductCategory extends BaseEditProductCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
