<?php

namespace Webkul\TimeOff\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

/**
 * My Time class
 *
 * @see \Filament\Resources\Resource
 */
class MyTime extends Cluster
{
    protected static ?int $navigationSort = 1;

    /**
     * Get Slug
     *
     * @param ?Panel $panel
     * @return string
     */
    public static function getSlug(?Panel $panel = null): string
    {
        return 'time-off/dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return __('time-off::filament/clusters/my-time.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('time-off::filament/clusters/my-time.navigation.group');
    }
}
