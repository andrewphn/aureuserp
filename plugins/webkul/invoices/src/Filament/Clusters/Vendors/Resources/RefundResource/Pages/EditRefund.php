<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\EditInvoice as BaseEditInvoice;

class EditRefund extends BaseEditInvoice
{
    protected static string $resource = RefundResource::class;
}
