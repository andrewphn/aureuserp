<?php

namespace Webkul\FullCalendar\Contracts;

/**
 * Has Header Actions interface
 *
 */
interface HasHeaderActions
{
    public function getCachedHeaderActions(): array;
}
