<?php

namespace Webkul\Employee\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

/**
 * Reportings class
 *
 * @see \Filament\Resources\Resource
 */
class Reportings extends Cluster
{
    protected static ?int $navigationSort = 3;

    /**
     * Get Slug
     *
     * @param ?Panel $panel
     * @return string
     */
    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/reportings';
    }

    public static function getNavigationLabel(): string
    {
        return __('employees::filament/clusters/reportings.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('employees::filament/clusters/reportings.navigation.group');
    }
}
