<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Tables\Table;
use Webkul\Recruitment\Filament\Clusters\Configurations;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\ActivityTypeResource\Pages;
use Webkul\Recruitment\Models\ActivityType;
use Webkul\Support\Filament\Resources\ActivityTypeResource as BaseActivityTypeResource;

class ActivityTypeResource extends BaseActivityTypeResource implements HasShieldPermissions
{
    protected static ?string $model = ActivityType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Configurations::class;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationGroup(): string
    {
        return __('recruitments::filament/clusters/configurations/resources/activity-type.navigation.group');
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

    public static function table(Table $table): Table
    {
        return BaseActivityTypeResource::table($table)
            ->modifyQueryUsing(function ($query) {
                return $query->where('plugin', 'recruitments');
            });
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListActivityTypes::route('/'),
            'create' => Pages\CreateActivityType::route('/create'),
            'edit'   => Pages\EditActivityType::route('/{record}/edit'),
            'view'   => Pages\ViewActivityType::route('/{record}'),
        ];
    }
}
