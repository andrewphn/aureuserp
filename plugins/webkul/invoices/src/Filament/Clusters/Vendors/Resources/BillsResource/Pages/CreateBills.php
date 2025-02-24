<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\CreateInvoice as BaseCreateInvoice;

class CreateBills extends BaseCreateInvoice
{
    protected static string $resource = BillsResource::class;
}
