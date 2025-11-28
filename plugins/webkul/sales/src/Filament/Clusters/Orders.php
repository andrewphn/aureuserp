<?php

namespace Webkul\Sale\Filament\Clusters;

use Filament\Clusters\Cluster;

/**
 * Orders class
 *
 * @see \Filament\Resources\Resource
 */
class Orders extends Cluster
{
    protected static ?string $slug = 'sale/orders';

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/orders.navigation.title');
    }

    /**
     * Get the navigation group
     *
     * @return string
     */
    public static function getNavigationGroup(): string
    {
        return __('sales::filament/clusters/orders.navigation.group');
    }
}
