<?php

namespace Webkul\Recruitment\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;

/**
 * Configurations class
 *
 * @see \Filament\Resources\Resource
 */
class Configurations extends Cluster
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
        return 'recruitments/configurations';
    }

    public static function getNavigationLabel(): string
    {
        return __('recruitments::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('recruitments::filament/clusters/configurations.navigation.group');
    }
}
