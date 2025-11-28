<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\DepartmentResource\Pages;

use Webkul\Employee\Filament\Resources\DepartmentResource\Pages\ListDepartments as BaseListDepartments;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\DepartmentResource;

/**
 * List Departments class
 *
 * @see \Filament\Resources\Resource
 */
class ListDepartments extends BaseListDepartments
{
    protected static string $resource = DepartmentResource::class;
}
