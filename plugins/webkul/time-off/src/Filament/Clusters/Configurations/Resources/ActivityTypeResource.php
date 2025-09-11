<?php

namespace Webkul\TimeOff\Filament\Clusters\Configurations\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Webkul\Support\Filament\Resources\ActivityTypeResource as BaseActivityTypeResource;
use Webkul\TimeOff\Filament\Clusters\Configurations;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\ActivityTypeResource\Pages\CreateActivityType;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\ActivityTypeResource\Pages\EditActivityType;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\ActivityTypeResource\Pages\ListActivityTypes;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\ActivityTypeResource\Pages\ViewActivityType;
use Webkul\TimeOff\Models\ActivityType;

class ActivityTypeResource extends BaseActivityTypeResource implements HasShieldPermissions
{
    protected static ?string $model = ActivityType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configurations::class;

    protected static ?string $pluginName = 'time-off';

    protected static ?int $navigationSort = 5;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view::time',
            'view_any::time',
            'create::time',
            'update::time',
            'restore::time',
            'restore_any::time',
            'replicate::time',
            'reorder::time',
            'delete::time',
            'delete_any::time',
            'force_delete::time',
            'force_delete_any::time',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListActivityTypes::route('/'),
            'create' => CreateActivityType::route('/create'),
            'edit'   => EditActivityType::route('/{record}/edit'),
            'view'   => ViewActivityType::route('/{record}'),
        ];
    }
}
