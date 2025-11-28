<?php

namespace Webkul\Project\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Time Settings class
 *
 */
class TimeSettings extends Settings
{
    public bool $enable_timesheets;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'time';
    }
}
