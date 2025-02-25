<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource\Pages\ManageMoves as BaseManageMoves;

class ManageMoves extends BaseManageMoves
{
    protected static string $resource = ProductResource::class;
}
