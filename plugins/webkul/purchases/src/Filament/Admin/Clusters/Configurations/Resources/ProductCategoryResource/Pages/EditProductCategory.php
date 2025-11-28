<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Webkul\Product\Filament\Resources\CategoryResource\Pages\EditCategory;
use Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\ProductCategoryResource;

/**
 * Edit Product Category class
 *
 * @see \Filament\Resources\Resource
 */
class EditProductCategory extends EditCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
