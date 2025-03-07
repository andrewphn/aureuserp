<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\OrdersResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\OrdersResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ManageInvoices as BaseManageInvoices;

class ManageInvoices extends BaseManageInvoices
{
    protected static string $resource = OrdersResource::class;
}
