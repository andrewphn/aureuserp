<?php

namespace Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductAttributeResource;
use Webkul\Product\Filament\Resources\AttributeResource\Pages\CreateAttribute;

class CreateProductAttribute extends CreateAttribute
{
    protected static string $resource = ProductAttributeResource::class;
}
