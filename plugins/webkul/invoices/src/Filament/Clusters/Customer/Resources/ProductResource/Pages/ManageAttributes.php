<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ManageAttributes as BaseManageAttributes;

/**
 * Manage Attributes class
 *
 * @see \Filament\Resources\Resource
 */
class ManageAttributes extends BaseManageAttributes
{
    protected static string $resource = ProductResource::class;
}
