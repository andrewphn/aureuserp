<?php

namespace Webkul\Sale\Filament\Clusters;

use Filament\Clusters\Cluster;

/**
 * Configuration class
 *
 * @see \Filament\Resources\Resource
 */
class Configuration extends Cluster
{
    protected static ?string $slug = 'sale/configurations';

    protected static ?int $navigationSort = 1;

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/configurations.navigation.title');
    }

    /**
     * Get the navigation group
     *
     * @return string
     */
    public static function getNavigationGroup(): string
    {
        return __('sales::filament/clusters/configurations.navigation.group');
    }
}
