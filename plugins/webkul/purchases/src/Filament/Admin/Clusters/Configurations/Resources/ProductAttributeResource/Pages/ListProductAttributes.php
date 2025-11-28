<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\ProductAttributeResource\Pages;

use Webkul\Product\Filament\Resources\AttributeResource\Pages\ListAttributes;
use Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\ProductAttributeResource;

/**
 * List Product Attributes class
 *
 * @see \Filament\Resources\Resource
 */
class ListProductAttributes extends ListAttributes
{
    protected static string $resource = ProductAttributeResource::class;
}
