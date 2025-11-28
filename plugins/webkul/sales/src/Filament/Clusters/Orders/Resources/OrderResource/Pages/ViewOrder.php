<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation as BaseViewOrders;

/**
 * View Order class
 *
 * @see \Filament\Resources\Resource
 */
class ViewOrder extends BaseViewOrders
{
    protected static string $resource = OrderResource::class;
}
