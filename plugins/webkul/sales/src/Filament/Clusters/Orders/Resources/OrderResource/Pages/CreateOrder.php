<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\CreateQuotation as BaseCreateOrders;

/**
 * Create Order class
 *
 * @see \Filament\Resources\Resource
 */
class CreateOrder extends BaseCreateOrders
{
    protected static string $resource = OrderResource::class;
}
