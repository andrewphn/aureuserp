<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\InvoiceResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ListInvoices as BaseListInvoices;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\InvoiceResource;

/**
 * List Invoices class
 *
 * @see \Filament\Resources\Resource
 */
class ListInvoices extends BaseListInvoices
{
    protected static string $resource = InvoiceResource::class;
}
