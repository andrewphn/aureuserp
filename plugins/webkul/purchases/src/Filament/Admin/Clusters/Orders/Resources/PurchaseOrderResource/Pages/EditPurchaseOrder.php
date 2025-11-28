<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\EditOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;

/**
 * Edit Purchase Order class
 *
 * @see \Filament\Resources\Resource
 */
class EditPurchaseOrder extends EditOrder
{
    protected static string $resource = PurchaseOrderResource::class;
}
