<?php

namespace Webkul\FullCalendar\Contracts;

/**
 * Has Modal Actions interface
 *
 */
interface HasModalActions
{
    public function getCachedModalActions(): array;
}
