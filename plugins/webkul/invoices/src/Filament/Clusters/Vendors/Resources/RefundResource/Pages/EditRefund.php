<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;

use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\EditInvoice as BaseEditInvoice;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource;

class EditRefund extends BaseEditInvoice
{
    protected static string $resource = RefundResource::class;
}
