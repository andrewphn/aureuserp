<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\SkillTypeResource\Pages;

use Webkul\Employee\Filament\Clusters\Configurations\Resources\SkillTypeResource\Pages\ViewSkillType as ViewSkillTypeBase;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\SkillTypeResource;

/**
 * View Skill Type class
 *
 * @see \Filament\Resources\Resource
 */
class ViewSkillType extends ViewSkillTypeBase
{
    protected static string $resource = SkillTypeResource::class;
}
