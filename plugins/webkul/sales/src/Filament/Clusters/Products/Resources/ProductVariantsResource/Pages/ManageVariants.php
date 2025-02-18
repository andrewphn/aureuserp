<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\ManageAttributes as BaseManageAttributes;

class ManageVariants extends BaseManageAttributes
{
    protected static string $resource = ProductVariantsResource::class;
}
