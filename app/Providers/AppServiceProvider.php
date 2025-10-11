<?php

namespace App\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerJavaScriptAssets();
    }

    /**
     * Register global JavaScript assets
     */
    protected function registerJavaScriptAssets(): void
    {
        FilamentAsset::register([
            Js::make('centralized-entity-store', resource_path('js/centralized-entity-store.js')),
        ], 'app');
    }
}
