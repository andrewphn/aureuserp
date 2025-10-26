<?php

namespace App\Providers;

use App\Filament\Widgets\GlobalContextFooter;
use App\Services\Footer\ContextRegistry;
use App\Services\Footer\Contexts\InventoryContextProvider;
use App\Services\Footer\Contexts\ProductionContextProvider;
use App\Services\Footer\Contexts\ProjectContextProvider;
use App\Services\Footer\Contexts\SaleContextProvider;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Footer Service Provider
 *
 * Registers the global footer system and all context providers.
 * Provides hooks for plugins to extend the footer functionality.
 */
class FooterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/footer.php',
            'footer'
        );

        // Register ContextRegistry as singleton
        $this->app->singleton(ContextRegistry::class, function ($app) {
            return new ContextRegistry();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/footer.php' => config_path('footer.php'),
        ], 'footer-config');

        // Register Livewire component for the footer widget
        Livewire::component('app.filament.widgets.global-context-footer', GlobalContextFooter::class);

        // Register core context providers
        $this->registerCoreContextProviders();

        // Fire event for plugins to register their own context providers
        $this->dispatchPluginRegistrationEvent();

        // Set up cache if enabled
        if (config('footer.cache.enabled')) {
            $this->setupCache();
        }
    }

    /**
     * Register the 4 core context providers.
     */
    protected function registerCoreContextProviders(): void
    {
        $registry = $this->app->make(ContextRegistry::class);

        // Get enabled contexts from config
        $enabledContexts = config('footer.enabled_contexts', [
            'project',
            'sale',
            'inventory',
            'production',
        ]);

        // Register each enabled context
        if (in_array('project', $enabledContexts)) {
            $registry->register(new ProjectContextProvider());
        }

        if (in_array('sale', $enabledContexts)) {
            $registry->register(new SaleContextProvider());
        }

        if (in_array('inventory', $enabledContexts)) {
            $registry->register(new InventoryContextProvider());
        }

        if (in_array('production', $enabledContexts)) {
            $registry->register(new ProductionContextProvider());
        }

        $this->app->instance(ContextRegistry::class, $registry);
    }

    /**
     * Dispatch event for plugins to register their own context providers.
     */
    protected function dispatchPluginRegistrationEvent(): void
    {
        if (config('footer.allow_plugin_contexts', true)) {
            // Plugins can listen to this event and register their own context providers
            // Example in plugin service provider:
            //
            // Event::listen('footer.register-contexts', function (ContextRegistry $registry) {
            //     $registry->register(new MyCustomContextProvider());
            // });
            //
            event('footer.register-contexts', [$this->app->make(ContextRegistry::class)]);
        }
    }

    /**
     * Set up caching for context data.
     */
    protected function setupCache(): void
    {
        // Cache configuration is already in config/footer.php
        // Context providers can use Laravel's Cache facade with the configured TTL
        // Example:
        //
        // Cache::remember(
        //     config('footer.cache.prefix') . ".{$contextType}.{$entityId}",
        //     config('footer.cache.ttl'),
        //     fn() => $this->loadContext($entityId)
        // );
    }
}
