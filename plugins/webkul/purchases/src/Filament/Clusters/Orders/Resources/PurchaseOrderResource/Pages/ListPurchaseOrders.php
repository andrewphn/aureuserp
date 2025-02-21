<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\ListOrders;
use Filament\Actions;

class ListPurchaseOrders extends ListOrders
{
    protected static string $resource = PurchaseOrderResource::class;
}
