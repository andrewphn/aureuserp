<?php

namespace Webkul\TimeOff\Filament\Clusters\Reporting\Resources\ByEmployeeResource\Pages;

use Webkul\TimeOff\Filament\Clusters\Management\Resources\TimeOffResource\Pages\ListTimeOff;
use Webkul\TimeOff\Filament\Clusters\Reporting\Resources\ByEmployeeResource;

/**
 * List By Employees class
 *
 * @see \Filament\Resources\Resource
 */
class ListByEmployees extends ListTimeOff
{
    protected static string $resource = ByEmployeeResource::class;
}
