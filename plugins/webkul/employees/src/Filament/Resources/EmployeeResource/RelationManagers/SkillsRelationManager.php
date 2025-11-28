<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Webkul\Employee\Traits\Resources\Employee\EmployeeSkillRelation;

/**
 * Skills Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class SkillsRelationManager extends RelationManager
{
    use EmployeeSkillRelation;

    protected static string $relationship = 'skills';

    protected static ?string $title = 'Skills';
}
