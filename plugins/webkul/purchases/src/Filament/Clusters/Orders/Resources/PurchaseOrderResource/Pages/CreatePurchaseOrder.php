<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\CreateOrder;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource;

class CreatePurchaseOrder extends CreateOrder
{
    protected static string $resource = PurchaseOrderResource::class;
}
