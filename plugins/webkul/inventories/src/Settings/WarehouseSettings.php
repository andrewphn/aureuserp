<?php

namespace Webkul\Inventory\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Warehouse Settings class
 *
 */
class WarehouseSettings extends Settings
{
    public bool $enable_locations;

    public bool $enable_multi_steps_routes;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'inventories_warehouse';
    }
}
