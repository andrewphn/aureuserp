<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\ListOrders;
use Filament\Actions;

class ListQuotations extends ListOrders
{
    protected static string $resource = QuotationResource::class;
}
