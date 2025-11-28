<?php

namespace Webkul\Inventory\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Traceability Settings class
 *
 */
class TraceabilitySettings extends Settings
{
    public bool $enable_lots_serial_numbers;

    public bool $enable_expiration_dates;

    public bool $display_on_delivery_slips;

    public bool $display_expiration_dates_on_delivery_slips;

    public bool $enable_consignments;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'inventories_traceability';
    }
}
