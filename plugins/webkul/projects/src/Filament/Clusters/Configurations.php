<?php

namespace Webkul\Project\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;

/**
 * Configurations class
 *
 * @see \Filament\Resources\Resource
 */
class Configurations extends Cluster
{
    protected static ?string $slug = 'project/configurations';

    protected static ?int $navigationSort = 0;

    // Hide from main navigation - accessible via Settings
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('webkul-project::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('webkul-project::filament/clusters/configurations.navigation.group');
    }
}
