<?php

namespace Webkul\Purchase\Filament\Clusters;

use Filament\Clusters\Cluster;

class Products extends Cluster
{
    protected static ?string $slug = 'purchase/products';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/clusters/products.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('purchases::filament/clusters/products.navigation.group');
    }
}
