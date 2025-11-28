<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\CreateOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource;

/**
 * Create Quotation class
 *
 * @see \Filament\Resources\Resource
 */
class CreateQuotation extends CreateOrder
{
    protected static string $resource = QuotationResource::class;
}
