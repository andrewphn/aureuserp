<?php

namespace Webkul\Lead;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\Support\Package;

/**
 * Lead Plugin class for FilamentPHP
 */
class LeadPlugin implements Plugin
{
    public function getId(): string
    {
        return 'leads';
    }

    /**
     * Make
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Register
     */
    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel
                    ->discoverResources(in: $this->getPluginBasePath('/Filament/Resources'), for: 'Webkul\\Lead\\Filament\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Pages'), for: 'Webkul\\Lead\\Filament\\Pages')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Clusters'), for: 'Webkul\\Lead\\Filament\\Clusters')
                    ->discoverWidgets(in: $this->getPluginBasePath('/Filament/Widgets'), for: 'Webkul\\Lead\\Filament\\Widgets');
            });
    }

    /**
     * Boot
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Get Plugin Base Path
     */
    protected function getPluginBasePath($path = null): string
    {
        $reflector = new ReflectionClass(get_class($this));

        return dirname($reflector->getFileName()).($path ?? '');
    }
}
