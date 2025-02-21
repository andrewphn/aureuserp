<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\ManageAttributes as BaseManageAttributes;

class ManageAttributes extends BaseManageAttributes
{
    protected static string $resource = ProductResource::class;
}
