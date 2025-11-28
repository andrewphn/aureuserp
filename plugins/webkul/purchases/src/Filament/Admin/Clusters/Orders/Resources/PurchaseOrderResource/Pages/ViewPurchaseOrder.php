<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;

/**
 * View Purchase Order class
 *
 * @see \Filament\Resources\Resource
 */
class ViewPurchaseOrder extends ViewOrder
{
    protected static string $resource = PurchaseOrderResource::class;
}
