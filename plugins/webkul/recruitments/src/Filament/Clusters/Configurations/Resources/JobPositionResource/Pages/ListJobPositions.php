<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\JobPositionResource\Pages;

use Webkul\Employee\Filament\Clusters\Configurations\Resources\JobPositionResource\Pages\ListJobPositions as BaseListJobPositions;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\JobPositionResource;

/**
 * List Job Positions class
 *
 * @see \Filament\Resources\Resource
 */
class ListJobPositions extends BaseListJobPositions
{
    protected static string $resource = JobPositionResource::class;
}
