<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\SkillTypeResource\Pages;

use Webkul\Employee\Filament\Clusters\Configurations\Resources\SkillTypeResource\Pages\EditSkillType as EditSkillTypeBase;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\SkillTypeResource;

/**
 * Edit Skill Type class
 *
 * @see \Filament\Resources\Resource
 */
class EditSkillType extends EditSkillTypeBase
{
    protected static string $resource = SkillTypeResource::class;
}
