<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityTypeResource\Pages;

use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityTypeResource;
use Webkul\Support\Filament\Resources\ActivityTypeResource\Pages\EditActivityType as BaseEditActivityType;

/**
 * Edit Activity Type class
 *
 * @see \Filament\Resources\Resource
 */
class EditActivityType extends BaseEditActivityType
{
    protected static string $resource = ActivityTypeResource::class;

    protected static ?string $pluginName = 'recruitments';
}
