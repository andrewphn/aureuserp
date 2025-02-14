<?php

namespace Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Product\Filament\Resources\AttributeResource\Pages\EditAttribute;
use Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductAttributeResource;

class EditProductAttribute extends EditAttribute
{
    protected static string $resource = ProductAttributeResource::class;
}
