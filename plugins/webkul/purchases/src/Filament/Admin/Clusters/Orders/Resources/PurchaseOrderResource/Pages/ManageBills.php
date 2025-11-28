<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\ManageBills as BaseManageBills;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;

/**
 * Manage Bills class
 *
 * @see \Filament\Resources\Resource
 */
class ManageBills extends BaseManageBills
{
    protected static string $resource = PurchaseOrderResource::class;
}
