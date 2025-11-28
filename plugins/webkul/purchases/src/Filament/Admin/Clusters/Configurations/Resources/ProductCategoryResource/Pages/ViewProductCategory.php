<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Product\Filament\Resources\CategoryResource\Pages\ViewCategory;
use Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\ProductCategoryResource;

/**
 * View Product Category class
 *
 * @see \Filament\Resources\Resource
 */
class ViewProductCategory extends ViewCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
