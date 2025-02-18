<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource;
use Webkul\Product\Filament\Resources\PriceListResource\Pages\ViewPriceList as BaseViewPriceList;

class ViewPriceList extends BaseViewPriceList
{
    protected static string $resource = PriceListResource::class;
}
