<?php

namespace Webkul\TimeOff\Filament\Clusters\Reporting\Resources\ByEmployeeResource\Pages;

use Webkul\TimeOff\Filament\Clusters\Management\Resources\TimeOffResource\Pages\ListTimeOff;
use Webkul\TimeOff\Filament\Clusters\Reporting\Resources\ByEmployeeResource;
use Webkul\TimeOff\Filament\Clusters\Management\Resources\TimeOffResource\Pages\ListTimeOffs as BaseListTimeOffs;

<<<<<<< Updated upstream
class ListByEmployees extends ListTimeOff
=======
class ListByEmployees extends BaseListTimeOffs
>>>>>>> Stashed changes
{
    protected static string $resource = ByEmployeeResource::class;
}
