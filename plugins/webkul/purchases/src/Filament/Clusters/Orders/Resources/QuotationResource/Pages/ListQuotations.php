<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\ListOrders;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource;

class ListQuotations extends ListOrders
{
    protected static string $resource = QuotationResource::class;
}
