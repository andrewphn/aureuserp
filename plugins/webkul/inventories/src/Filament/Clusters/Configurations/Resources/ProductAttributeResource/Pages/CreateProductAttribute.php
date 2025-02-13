<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Product\Filament\Resources\AttributeResource\Pages\CreateAttribute;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductAttributeResource;

class CreateProductAttribute extends CreateAttribute
{
    protected static string $resource = ProductAttributeResource::class;
}
