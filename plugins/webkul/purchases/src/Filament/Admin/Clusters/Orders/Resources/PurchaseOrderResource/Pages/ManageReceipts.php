<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\ManageReceipts as BaseManageReceipts;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;

/**
 * Manage Receipts class
 *
 * @see \Filament\Resources\Resource
 */
class ManageReceipts extends BaseManageReceipts
{
    protected static string $resource = PurchaseOrderResource::class;
}
