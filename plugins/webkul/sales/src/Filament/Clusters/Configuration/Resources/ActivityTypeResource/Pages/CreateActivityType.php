<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource\Pages;

use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource;
use Webkul\Support\Filament\Resources\ActivityTypeResource\Pages\CreateActivityType as BaseCreateActivityType;

/**
 * Create Activity Type class
 *
 * @see \Filament\Resources\Resource
 */
class CreateActivityType extends BaseCreateActivityType
{
    protected static string $resource = ActivityTypeResource::class;
}
