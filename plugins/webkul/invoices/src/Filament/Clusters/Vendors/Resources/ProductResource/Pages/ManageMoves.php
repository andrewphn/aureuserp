<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\ManageMoves as BaseManageMoves;

class ManageMoves extends BaseManageMoves
{
    protected static string $resource = ProductResource::class;
}
