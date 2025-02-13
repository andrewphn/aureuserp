<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Product\Filament\Resources\AttributeResource\Pages\ListAttributes;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductAttributeResource;

class ListProductAttributes extends ListAttributes
{
    protected static string $resource = ProductAttributeResource::class;
}
