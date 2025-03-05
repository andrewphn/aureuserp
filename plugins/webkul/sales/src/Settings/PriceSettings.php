<?php

namespace Webkul\Sale\Settings;

use Spatie\LaravelSettings\Settings;

class PriceSettings extends Settings
{
    public bool $enable_discount;

    public static function group(): string
    {
        return 'sales_price';
    }
}
