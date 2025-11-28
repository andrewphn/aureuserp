<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductAttributeResource;
use Webkul\Product\Filament\Resources\AttributeResource\Pages\ListAttributes;

/**
 * List Product Attributes class
 *
 * @see \Filament\Resources\Resource
 */
class ListProductAttributes extends ListAttributes
{
    protected static string $resource = ProductAttributeResource::class;
}
