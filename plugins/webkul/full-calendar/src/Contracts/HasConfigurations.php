<?php

namespace Webkul\FullCalendar\Contracts;

/**
 * Has Configurations interface
 *
 */
interface HasConfigurations
{
    /**
     * Config
     *
     * @return array
     */
    public function config(): array;

    public function getConfig(): array;
}
