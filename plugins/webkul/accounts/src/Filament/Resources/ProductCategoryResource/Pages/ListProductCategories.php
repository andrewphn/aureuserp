<?php

namespace Webkul\Account\Filament\Resources\ProductCategoryResource\Pages;

use Webkul\Account\Filament\Resources\ProductCategoryResource;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ListProductCategories as BaseListProductCategories;

class ListProductCategories extends BaseListProductCategories
{
    protected static string $resource = ProductCategoryResource::class;
}
