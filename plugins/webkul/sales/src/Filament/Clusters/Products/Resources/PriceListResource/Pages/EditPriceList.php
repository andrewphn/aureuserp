<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource\Pages;

use Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource;
use Webkul\Product\Filament\Resources\PriceListResource\Pages\EditPriceList as BaseEditPriceList;

class EditPriceList extends BaseEditPriceList
{
    protected static string $resource = PriceListResource::class;
}
