<?php

namespace Webkul\Purchase\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Purchase Order class
 *
 */
class PurchaseOrder extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'purchase_order';
    }
}
