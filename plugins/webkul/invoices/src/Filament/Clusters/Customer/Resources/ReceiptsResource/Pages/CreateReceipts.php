<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\CreateInvoice as BaseCreateInvoice;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource;

class CreateReceipts extends BaseCreateInvoice
{
    protected static string $resource = ReceiptsResource::class;
}
