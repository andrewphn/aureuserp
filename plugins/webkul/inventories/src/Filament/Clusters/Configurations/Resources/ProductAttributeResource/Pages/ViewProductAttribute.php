<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Product\Filament\Resources\AttributeResource\Pages\ViewAttribute;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductAttributeResource;

class ViewProductAttribute extends ViewAttribute
{
    protected static string $resource = ProductAttributeResource::class;
}
