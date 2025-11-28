<?php

namespace Webkul\Employee\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

/**
 * Configurations class
 *
 * @see \Filament\Resources\Resource
 */
class Configurations extends Cluster
{
    protected static ?int $navigationSort = 4;

    /**
     * Get Slug
     *
     * @param ?Panel $panel
     * @return string
     */
    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('employees::filament/clusters/configurations.navigation.group');
    }
}
