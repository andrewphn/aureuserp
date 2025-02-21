<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\EditOrder;
use Filament\Actions;

class EditQuotation extends EditOrder
{
    protected static string $resource = QuotationResource::class;
}
