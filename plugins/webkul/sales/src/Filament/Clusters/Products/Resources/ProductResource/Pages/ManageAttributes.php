<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource\Pages\ManageAttributes as BaseManageAttributes;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource;

/**
 * Manage Attributes class
 *
 * @see \Filament\Resources\Resource
 */
class ManageAttributes extends BaseManageAttributes
{
    protected static string $resource = ProductResource::class;
}
