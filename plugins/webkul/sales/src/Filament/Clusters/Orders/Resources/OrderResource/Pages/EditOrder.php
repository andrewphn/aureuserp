<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation as BaseEditOrder;

/**
 * Edit Order class
 *
 * @see \Filament\Resources\Resource
 */
class EditOrder extends BaseEditOrder
{
    protected static string $resource = OrderResource::class;
}
