<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\CreateOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;

/**
 * Create Purchase Order class
 *
 * @see \Filament\Resources\Resource
 */
class CreatePurchaseOrder extends CreateOrder
{
    protected static string $resource = PurchaseOrderResource::class;
}
