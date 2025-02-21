<?php

namespace Webkul\Account\Filament\Resources\ProductCategoryResource\Pages;

use Webkul\Account\Filament\Resources\ProductCategoryResource;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ViewProductCategory as BaseViewProductCategory;

class ViewProductCategory extends BaseViewProductCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
