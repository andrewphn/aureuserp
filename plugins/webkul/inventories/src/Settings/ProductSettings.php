<?php

namespace Webkul\Inventory\Settings;

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
        return 'inventories_product';
    }
}
