<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\ViewInvoice as BaseViewInvoice;

class ViewReceipts extends BaseViewInvoice
{
    protected static string $resource = ReceiptsResource::class;
}
