<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource\Pages;

use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\EditInvoice as BaseEditInvoice;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource;

class EditBills extends BaseEditInvoice
{
    protected static string $resource = BillsResource::class;
}
