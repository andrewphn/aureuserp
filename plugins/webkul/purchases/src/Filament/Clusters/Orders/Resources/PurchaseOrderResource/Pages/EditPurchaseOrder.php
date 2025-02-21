<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\EditOrder;
use Filament\Actions;

class EditPurchaseOrder extends EditOrder
{
    protected static string $resource = PurchaseOrderResource::class;
}
