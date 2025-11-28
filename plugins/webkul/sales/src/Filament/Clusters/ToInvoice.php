<?php

namespace Webkul\Sale\Filament\Clusters;

use Filament\Clusters\Cluster;

/**
 * To Invoice class
 *
 * @see \Filament\Resources\Resource
 */
class ToInvoice extends Cluster
{
    protected static ?string $slug = 'sale/invoice';

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/to-invoice.navigation.title');
    }

    /**
     * Get the navigation group
     *
     * @return string
     */
    public static function getNavigationGroup(): string
    {
        return __('sales::filament/clusters/to-invoice.navigation.group');
    }
}
