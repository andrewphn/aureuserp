<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ViewInvoice as BaseEditInvoice;

class ViewRefund extends BaseEditInvoice
{
    protected static string $resource = RefundResource::class;
}
