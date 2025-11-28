<?php

namespace App\Providers;

use App\Services\AnnotationService;
use App\Services\FooterFieldRegistry;
use App\Services\FooterPreferenceService;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * App Service Provider service provider
 *
 * @see \Illuminate\Support\ServiceProvider
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register footer customizer services as singletons
        $this->app->singleton(FooterFieldRegistry::class);
        $this->app->singleton(FooterPreferenceService::class);

        // Register annotation service as singleton
        $this->app->singleton(AnnotationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Scripts are now bundled via Vite in app.js - no need for separate registration
        // $this->registerJavaScriptAssets();
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
     *
     * DISABLED: These scripts are now bundled in app.js via Vite
     * to prevent double-loading and reduce console noise
     */
    protected function registerJavaScriptAssets(): void
    {
        // Scripts are imported in resources/js/app.js
        // No need for separate FilamentAsset registration

        // FilamentAsset::register([
        //     Js::make('centralized-entity-store', __DIR__ . '/../../resources/js/centralized-entity-store.js'),
        //     Js::make('form-auto-populate', __DIR__ . '/../../resources/js/form-auto-populate.js'),
        // ], 'app');
    }
}
