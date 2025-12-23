<?php

namespace Webkul\Lead;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Webkul\Lead\Http\Middleware\SpamProtectionMiddleware;
use Webkul\Lead\Livewire\ContactForm;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

/**
 * Lead Service Provider
 */
class LeadServiceProvider extends PackageServiceProvider
{
    public static string $name = 'leads';

    /**
     * Configure Custom Package
     */
    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2025_12_23_000001_create_leads_table',
            ])
            ->runsMigrations();
    }

    /**
     * Package Booted
     */
    public function packageBooted(): void
    {
        // Register rate limiter for contact form
        RateLimiter::for('contact-form', function ($request) {
            return Limit::perHour(5)->by($request->ip());
        });

        // Register Livewire components
        Livewire::component('contact-form', ContactForm::class);

        // Register middleware alias
        $this->app['router']->aliasMiddleware('spam.protection', SpamProtectionMiddleware::class);

        // Register API routes
        $this->registerApiRoutes();
    }

    /**
     * Register API routes for contact form
     */
    protected function registerApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware(['api'])
            ->group(function () {
                Route::post('/contact', [\Webkul\Lead\Http\Controllers\Api\ContactController::class, 'store'])
                    ->middleware(['spam.protection', 'throttle:contact-form'])
                    ->name('api.contact.store');

                Route::post('/contact/check-customer', [\Webkul\Lead\Http\Controllers\Api\ContactController::class, 'checkCustomer'])
                    ->name('api.contact.check-customer');
            });
    }
}
