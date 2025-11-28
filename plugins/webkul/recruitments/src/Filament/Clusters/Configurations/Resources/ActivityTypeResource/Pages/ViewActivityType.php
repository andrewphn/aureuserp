<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityTypeResource\Pages;

use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityTypeResource;
use Webkul\Support\Filament\Resources\ActivityTypeResource\Pages\ViewActivityType as BaseViewActivityType;

/**
 * View Activity Type class
 *
 * @see \Filament\Resources\Resource
 */
class ViewActivityType extends BaseViewActivityType
{
    protected static string $resource = ActivityTypeResource::class;
}
