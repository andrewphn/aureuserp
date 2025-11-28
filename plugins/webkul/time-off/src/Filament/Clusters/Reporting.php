<?php

namespace Webkul\TimeOff\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

/**
 * Reporting class
 *
 * @see \Filament\Resources\Resource
 */
class Reporting extends Cluster
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
        return 'time-off/reporting';
    }

    public static function getNavigationLabel(): string
    {
        return __('time-off::filament/clusters/reporting.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('time-off::filament/clusters/reporting.navigation.group');
    }
}
