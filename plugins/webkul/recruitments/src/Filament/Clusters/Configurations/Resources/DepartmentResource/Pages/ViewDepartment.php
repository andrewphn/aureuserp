<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\DepartmentResource\Pages;

use Webkul\Employee\Filament\Resources\DepartmentResource\Pages\ViewDepartment as BaseViewDepartment;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\DepartmentResource;

/**
 * View Department class
 *
 * @see \Filament\Resources\Resource
 */
class ViewDepartment extends BaseViewDepartment
{
    protected static string $resource = DepartmentResource::class;
}
