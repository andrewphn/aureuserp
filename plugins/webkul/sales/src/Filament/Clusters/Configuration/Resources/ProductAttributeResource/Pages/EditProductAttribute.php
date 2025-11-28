<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductAttributeResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductAttributeResource\Pages\EditProductAttribute as BaseEditProductAttribute;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductAttributeResource;

/**
 * Edit Product Attribute class
 *
 * @see \Filament\Resources\Resource
 */
class EditProductAttribute extends BaseEditProductAttribute
{
    protected static string $resource = ProductAttributeResource::class;
}
