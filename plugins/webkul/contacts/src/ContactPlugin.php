<?php

namespace Webkul\Contact;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\Support\Package;

/**
 * Contact Plugin class
 *
 */
class ContactPlugin implements Plugin
{
    public function getId(): string
    {
        return 'contacts';
    }

    /**
     * Make
     *
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Register
     *
     * @param Panel $panel
     * @return void
     */
    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled('contacts')) {
            return;
        }

        $panel
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel->discoverResources(in: $this->getPluginBasePath('/Filament/Resources'), for: 'Webkul\\Contact\\Filament\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Pages'), for: 'Webkul\\Contact\\Filament\\Pages')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Clusters'), for: 'Webkul\\Contact\\Filament\\Clusters')
                    ->discoverWidgets(in: $this->getPluginBasePath('/Filament/Widgets'), for: 'Webkul\\Contact\\Filament\\Widgets');
            });
    }

    /**
     * Boot
     *
     * @param Panel $panel
     * @return void
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Get Plugin Base Path
     *
     * @param mixed $path
     * @return string
     */
    protected function getPluginBasePath($path = null): string
    {
        $reflector = new ReflectionClass(get_class($this));

        return dirname($reflector->getFileName()).($path ?? '');
    }
}
