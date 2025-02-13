<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Product\Filament\Resources\CategoryResource\Pages\ViewCategory;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource;

class ViewProductCategory extends ViewCategory
{
    protected static string $resource = ProductCategoryResource::class;
}
