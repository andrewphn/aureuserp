<?php

namespace Webkul\Sale\Filament\Clusters\ToInvoice\Resources\OrderToInvoiceResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation as BaseViewQuotation;
use Webkul\Sale\Filament\Clusters\ToInvoice\Resources\OrderToInvoiceResource;

/**
 * View Order To Invoice class
 *
 * @see \Filament\Resources\Resource
 */
class ViewOrderToInvoice extends BaseViewQuotation
{
    protected static string $resource = OrderToInvoiceResource::class;
}
