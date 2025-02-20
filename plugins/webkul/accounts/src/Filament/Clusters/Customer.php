<?php

namespace Webkul\Account\Filament\Clusters;

use Filament\Clusters\Cluster;

class Customer extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('accounts::filament/clusters/customers.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('accounts::filament/clusters/customers.navigation.group');
    }
}
