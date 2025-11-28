<?php

namespace Webkul\Purchase\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Order Settings class
 *
 */
class OrderSettings extends Settings
{
    public bool $enable_order_approval;

    public float $order_validation_amount;

    public bool $enable_lock_confirmed_orders;

    public bool $enable_purchase_agreements;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'purchases_order';
    }
}
