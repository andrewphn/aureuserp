<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\CreateOrder;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource;
use Filament\Actions;

class CreateQuotation extends CreateOrder
{
    protected static string $resource = QuotationResource::class;
}
