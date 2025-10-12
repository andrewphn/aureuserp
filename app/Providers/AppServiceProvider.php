<?php

namespace App\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Define the 'api' rate limiter (60 requests per minute)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Register global JavaScript assets
     */
    protected function registerJavaScriptAssets(): void
    {
        FilamentAsset::register([
            Js::make('centralized-entity-store', public_path('build/assets/centralized-entity-store-CUdMFKpf.js'))
                ->loadedOnRequest(),
            Js::make('form-auto-populate', public_path('build/assets/form-auto-populate-CMb-TTt1.js'))
                ->loadedOnRequest(),
        ], 'app');
    }
}
