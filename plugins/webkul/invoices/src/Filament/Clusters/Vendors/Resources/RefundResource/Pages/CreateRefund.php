<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\CreateInvoice as BaseCreateInvoice;

class CreateRefund extends BaseCreateInvoice
{
    protected static string $resource = RefundResource::class;
}
