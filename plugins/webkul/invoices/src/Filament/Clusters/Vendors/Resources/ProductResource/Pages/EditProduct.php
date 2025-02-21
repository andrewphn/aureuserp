<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages\EditProduct as BaseEditProduct;

class EditProduct extends BaseEditProduct
{
    protected static string $resource = ProductResource::class;
}
