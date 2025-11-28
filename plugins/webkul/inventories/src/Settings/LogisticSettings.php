<?php

namespace Webkul\Inventory\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Logistic Settings class
 *
 */
class LogisticSettings extends Settings
{
    public bool $enable_dropshipping;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'inventories_logistic';
    }
}
