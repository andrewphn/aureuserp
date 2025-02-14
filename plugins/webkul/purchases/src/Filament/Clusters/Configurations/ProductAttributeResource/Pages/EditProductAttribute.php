<?php

namespace Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductAttributeResource;
use Webkul\Product\Filament\Resources\AttributeResource\Pages\EditAttribute;

class EditProductAttribute extends EditAttribute
{
    protected static string $resource = ProductAttributeResource::class;
}
