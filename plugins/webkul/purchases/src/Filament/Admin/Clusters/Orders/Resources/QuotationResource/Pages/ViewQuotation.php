<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource;

/**
 * View Quotation class
 *
 * @see \Filament\Resources\Resource
 */
class ViewQuotation extends ViewOrder
{
    protected static string $resource = QuotationResource::class;
}
