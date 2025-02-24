<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\CreateInvoice as BaseCreateInvoice;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource;

class CreateRefund extends BaseCreateInvoice
{
    protected static string $resource = RefundResource::class;
}
