<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\SkillTypeResource as BaseSkillTypeResource;
use Webkul\Recruitment\Filament\Clusters\Configurations;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\SkillTypeResource\Pages;
use Webkul\Recruitment\Models\SkillType;

class SkillTypeResource extends BaseSkillTypeResource implements HasShieldPermissions
{
    protected static ?string $model = SkillType::class;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationGroup(): string
    {
        return __('recruitments::filament/clusters/configurations/resources/skill-type.navigation.group');
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view::recruitment',
            'view_any::recruitment',
            'create::recruitment',
            'update::recruitment',
            'restore::recruitment',
            'restore_any::recruitment',
            'replicate::recruitment',
            'reorder::recruitment',
            'delete::recruitment',
            'delete_any::recruitment',
            'force_delete::recruitment',
            'force_delete_any::recruitment',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSkillTypes::route('/'),
            'view'   => Pages\ViewSkillType::route('/{record}'),
            'edit'   => Pages\EditSkillType::route('/{record}/edit'),
        ];
    }
}
