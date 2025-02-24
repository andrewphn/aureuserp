<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ViewInvoice as BaseViewInvoice;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource;

class ViewReceipts extends BaseViewInvoice
{
    protected static string $resource = ReceiptsResource::class;
}
