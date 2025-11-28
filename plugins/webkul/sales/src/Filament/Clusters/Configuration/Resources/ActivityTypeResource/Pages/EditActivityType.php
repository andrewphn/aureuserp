<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource\Pages;

use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource;
use Webkul\Support\Filament\Resources\ActivityTypeResource\Pages\EditActivityType as BaseEditActivityType;

/**
 * Edit Activity Type class
 *
 * @see \Filament\Resources\Resource
 */
class EditActivityType extends BaseEditActivityType
{
    protected static string $resource = ActivityTypeResource::class;
}
