<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityPlanResource\Pages;

use Webkul\Employee\Filament\Clusters\Configurations\Resources\ActivityPlanResource\Pages\ListActivityPlans as BaseListActivityPlans;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityPlanResource;

/**
 * List Activity Plans class
 *
 * @see \Filament\Resources\Resource
 */
class ListActivityPlans extends BaseListActivityPlans
{
    protected static string $resource = ActivityPlanResource::class;

    protected static ?string $pluginName = 'recruitments';
}
