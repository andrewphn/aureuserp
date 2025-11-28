<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\EditProductCategory as BaseEditProductCategory;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource;

/**
 * Edit Product Category class
 *
 * @see \Filament\Resources\Resource
 */
class EditProductCategory extends BaseEditProductCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
