<?php

namespace Webkul\Website\Filament\Customer\Clusters;

use Filament\Clusters\Cluster;

/**
 * Account class
 *
 * @see \Filament\Resources\Resource
 */
class Account extends Cluster
{
    protected static ?int $navigationSort = 1000;

    public static function getNavigationLabel(): string
    {
        return __('website::filament/customer/clusters/account.navigation.title');
    }

    // public static function canAccess(): bool
    // {
    //     return false;
    //     return filament()->auth()->check();
    // }

    /**
     * Can Access Clustered Components
     *
     * @return bool
     */
    public static function canAccessClusteredComponents(): bool
    {
        return false;

        return filament()->auth()->check();
    }

    /**
     * Should Register Navigation
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
