<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\CreateProductCategory as BaseCreateProductCategory;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource;

/**
 * Create Product Category class
 *
 * @see \Filament\Resources\Resource
 */
class CreateProductCategory extends BaseCreateProductCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
