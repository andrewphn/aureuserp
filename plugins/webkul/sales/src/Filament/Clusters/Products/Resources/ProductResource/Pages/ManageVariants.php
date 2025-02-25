<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource\Pages\ManageVariants as BaseManageVariants;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource;

class ManageVariants extends BaseManageVariants
{
    protected static string $resource = ProductResource::class;
}
