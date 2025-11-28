<?php

namespace Webkul\TimeOff\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

/**
 * Overview class
 *
 * @see \Filament\Resources\Resource
 */
class Overview extends Cluster
{
    protected static ?int $navigationSort = 2;

    /**
     * Get Slug
     *
     * @param ?Panel $panel
     * @return string
     */
    public static function getSlug(?Panel $panel = null): string
    {
        return 'time-off/overview';
    }

    public static function getNavigationLabel(): string
    {
        return __('time-off::filament/clusters/overview.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('time-off::filament/clusters/overview.navigation.group');
    }
}
