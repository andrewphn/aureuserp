<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources;

use Webkul\Sale\Filament\Clusters\Configuration;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource\Pages\CreateActivityType;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource\Pages\EditActivityType;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource\Pages\ListActivityTypes;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityTypeResource\Pages\ViewActivityType;
use Webkul\Sale\Models\ActivityType;
use Webkul\Support\Filament\Resources\ActivityTypeResource as BaseActivityTypeResource;

/**
 * Activity Type Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class ActivityTypeResource extends BaseActivityTypeResource
{
    protected static ?string $model = ActivityType::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    /**
     * Get the model label
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('Activity Type');
    }

    /**
     * Get the navigation group
     *
     * @return string|null
     */
    public static function getNavigationGroup(): ?string
    {
        return __('Activities');
    }

    /**
     * Get the pages for this resource
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
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
