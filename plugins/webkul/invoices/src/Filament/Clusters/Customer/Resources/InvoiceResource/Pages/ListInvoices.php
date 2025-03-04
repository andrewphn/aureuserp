<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\InvoiceResource\Pages;

use Filament\Pages\SubNavigationPosition;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ListInvoices as BaseListInvoices;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\InvoiceResource;

class ListInvoices extends BaseListInvoices
{
    protected static string $resource = InvoiceResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Start;
    }
}
