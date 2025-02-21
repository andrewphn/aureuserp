<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource\Pages;

use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\CreateInvoice as BaseCreateInvoice;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource;

class CreateBills extends BaseCreateInvoice
{
    protected static string $resource = BillsResource::class;
}
