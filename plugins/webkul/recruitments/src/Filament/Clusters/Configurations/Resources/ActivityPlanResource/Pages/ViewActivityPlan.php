<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityPlanResource\Pages;

use Webkul\Employee\Filament\Clusters\Configurations\Resources\ActivityPlanResource\Pages\ViewActivityPlan as BaseViewActivityPlan;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityPlanResource;

/**
 * View Activity Plan class
 *
 * @see \Filament\Resources\Resource
 */
class ViewActivityPlan extends BaseViewActivityPlan
{
    protected static string $resource = ActivityPlanResource::class;
}
