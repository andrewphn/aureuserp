<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ViewProductCategory as BaseViewProductCategory;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource;

/**
 * View Product Category class
 *
 * @see \Filament\Resources\Resource
 */
class ViewProductCategory extends BaseViewProductCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
