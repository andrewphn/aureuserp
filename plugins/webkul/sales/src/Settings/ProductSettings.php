<?php

namespace Webkul\Sale\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Product Settings class
 *
 */
class ProductSettings extends Settings
{
    public bool $enable_variants;

    public bool $enable_uom;

    public bool $enable_packagings;

    public bool $enable_deliver_content_by_email;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'sales_product';
    }
}
