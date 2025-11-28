<?php

namespace Webkul\Invoice\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Product Settings class
 *
 */
class ProductSettings extends Settings
{
    public bool $enable_uom;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'invoices_products';
    }
}
