<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Webkul\Employee\Traits\Resources\Employee\EmployeeResumeRelation;

/**
 * Resume Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class ResumeRelationManager extends RelationManager
{
    use EmployeeResumeRelation;

    protected static string $relationship = 'resumes';

    protected static ?string $title = 'Resumes';
}
