<?php

namespace Webkul\Account\Filament\Clusters;

use Filament\Clusters\Cluster;

class Configuration extends Cluster
{
    protected static ?string $slug = 'accounts/configurations';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('accounts::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('accounts::filament/clusters/configurations.navigation.group');
    }
}
