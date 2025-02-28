<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\ManageBills as BaseManageBills;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource;

class ManageBills extends BaseManageBills
{
    protected static string $resource = PurchaseOrderResource::class;
}
