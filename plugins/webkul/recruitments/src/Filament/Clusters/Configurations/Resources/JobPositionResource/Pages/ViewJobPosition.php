<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\JobPositionResource\Pages;

use Webkul\Employee\Filament\Clusters\Configurations\Resources\JobPositionResource\Pages\ViewJobPosition as BaseViewJobPosition;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\JobPositionResource;

/**
 * View Job Position class
 *
 * @see \Filament\Resources\Resource
 */
class ViewJobPosition extends BaseViewJobPosition
{
    protected static string $resource = JobPositionResource::class;
}
