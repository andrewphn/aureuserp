<?php

namespace Webkul\Purchase\Settings;

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

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'purchases_product';
    }
}
