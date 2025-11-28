<?php

namespace Webkul\Sale\Filament\Clusters;

use Filament\Clusters\Cluster;

/**
 * Products class
 *
 * @see \Filament\Resources\Resource
 */
class Products extends Cluster
{
    protected static ?string $slug = 'sale/products';

    protected static ?int $navigationSort = 0;

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/products.navigation.title');
    }

    /**
     * Get the navigation group
     *
     * @return string
     */
    public static function getNavigationGroup(): string
    {
        return __('sales::filament/clusters/products.navigation.group');
    }
}
