<?php

namespace Webkul\Security;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\Security\Settings\UserSettings;

/**
 * Security Plugin class
 *
 */
class SecurityPlugin implements Plugin
{
    public function getId(): string
    {
        return 'security';
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
        $panel
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel->passwordReset()
                    ->discoverResources(in: $this->getPluginBasePath('/Filament/Resources'), for: 'Webkul\\Security\\Filament\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Pages'), for: 'Webkul\\Security\\Filament\\Pages')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Clusters'), for: 'Webkul\\Security\\Filament\\Clusters')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Widgets'), for: 'Webkul\\Security\\Filament\\Widgets');
            });

        if (
            ! app()->runningInConsole() &&
            ! app(UserSettings::class)?->enable_reset_password
        ) {
            $panel->passwordReset(false);
        }
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
