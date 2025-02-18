<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource;
use Webkul\Product\Filament\Resources\PriceListResource\Pages\ListPriceLists as BaseListPriceLists;

class ListPriceLists extends BaseListPriceLists
{
    protected static string $resource = PriceListResource::class;
}
