<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ViewInvoice as BaseEditInvoice;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource;

class ViewRefund extends BaseEditInvoice
{
    protected static string $resource = RefundResource::class;
}
