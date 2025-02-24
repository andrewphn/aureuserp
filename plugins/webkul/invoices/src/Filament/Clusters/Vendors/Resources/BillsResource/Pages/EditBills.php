<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\EditInvoice as BaseEditInvoice;

class EditBills extends BaseEditInvoice
{
    protected static string $resource = BillsResource::class;
}
