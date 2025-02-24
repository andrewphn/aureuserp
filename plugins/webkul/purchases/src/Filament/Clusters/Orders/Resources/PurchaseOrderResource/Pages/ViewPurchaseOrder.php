<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource;

class ViewPurchaseOrder extends ViewOrder
{
    protected static string $resource = PurchaseOrderResource::class;
}
