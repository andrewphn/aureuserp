<?php

namespace Webkul\Sale\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Price Settings class
 *
 */
class PriceSettings extends Settings
{
    public bool $enable_discount;

    public bool $enable_margin;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'sales_price';
    }
}
