<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\SkillTypeResource\Pages;

use Webkul\Employee\Filament\Clusters\Configurations\Resources\SkillTypeResource\Pages\ListSkillTypes as ListSkillTypesBase;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\SkillTypeResource;

/**
 * List Skill Types class
 *
 * @see \Filament\Resources\Resource
 */
class ListSkillTypes extends ListSkillTypesBase
{
    protected static string $resource = SkillTypeResource::class;
}
