<?php

namespace Webkul\Recruitment\Filament\Clusters\Applications\Resources\ApplicantResource\Pages;

use Webkul\Recruitment\Filament\Clusters\Applications\Resources\ApplicantResource;
use Webkul\Recruitment\Filament\Clusters\Applications\Resources\CandidateResource\Pages\ManageSkill as BaseManageSkill;

/**
 * Manage Skill class
 *
 * @see \Filament\Resources\Resource
 */
class ManageSkill extends BaseManageSkill
{
    protected static string $resource = ApplicantResource::class;
}
