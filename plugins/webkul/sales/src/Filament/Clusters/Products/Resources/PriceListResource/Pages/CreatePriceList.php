<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource;
use Webkul\Product\Filament\Resources\PriceListResource\Pages\CreatePriceList as BaseCreatePriceList;

class CreatePriceList extends BaseCreatePriceList
{
    protected static string $resource = PriceListResource::class;
}
