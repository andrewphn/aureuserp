<?php

namespace Webkul\TimeOff\Filament\Clusters\Configurations\Resources\ActivityTypeResource\Pages;

use Webkul\Support\Filament\Resources\ActivityTypeResource\Pages\ListActivityTypes as BaseListActivityTypes;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\ActivityTypeResource;

/**
 * List Activity Types class
 *
 * @see \Filament\Resources\Resource
 */
class ListActivityTypes extends BaseListActivityTypes
{
    protected static string $resource = ActivityTypeResource::class;

    protected static ?string $pluginName = 'time-off';
}
