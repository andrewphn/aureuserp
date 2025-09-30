<?php

namespace Webkul\Sale\Settings;

use Spatie\LaravelSettings\Settings;

class QuotationAndOrderSettings extends Settings
{
    public int $default_quotation_validity;

    public bool $enable_lock_confirm_sales;

    public string $quotation_prefix;

    public string $sales_order_prefix;

    public static function group(): string
    {
        return 'sales_quotation_and_orders';
    }
}
