<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Filament\Actions;

class ViewQuotation extends ViewOrder
{
    protected static string $resource = QuotationResource::class;
}
