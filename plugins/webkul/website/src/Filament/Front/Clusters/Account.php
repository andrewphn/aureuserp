<?php

namespace Webkul\Website\Filament\Front\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Filament\Http\Middleware\Authenticate;

class Account extends Cluster
{
    protected static ?int $navigationSort = 1000;

    public static function getNavigationLabel(): string
    {
        return __('website::filament/front/clusters/account.navigation.title');
    }
    
    // public static function canAccess(): bool
    // {
    //     return false;
    //     return filament()->auth()->check();
    // }
    
    public static function canAccessClusteredComponents(): bool
    {
        return false;
        return filament()->auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
