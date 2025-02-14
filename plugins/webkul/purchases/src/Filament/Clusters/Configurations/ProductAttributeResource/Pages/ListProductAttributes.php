<?php

namespace Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductAttributeResource;
use Webkul\Product\Filament\Resources\AttributeResource\Pages\ListAttributes;

class ListProductAttributes extends ListAttributes
{
    protected static string $resource = ProductAttributeResource::class;
}
