<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\ListInvoices as BaseListInvoices;

class ListReceipts extends BaseListInvoices
{
    protected static string $resource = ReceiptsResource::class;
}
