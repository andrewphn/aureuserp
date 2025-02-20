<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\CreateInvoice as BaseCreateInvoice;

class CreateReceipts extends BaseCreateInvoice
{
    protected static string $resource = ReceiptsResource::class;
}
