<?php

namespace Webkul\Security\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Currency Settings class
 *
 */
class CurrencySettings extends Settings
{
    public ?int $default_currency_id;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'currency';
    }
}
