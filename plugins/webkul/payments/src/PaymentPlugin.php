<?php

namespace Webkul\Payment;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\Support\Package;

/**
 * Payment Plugin class
 *
 */
class PaymentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'payments';
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
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel->discoverResources(in: $this->getPluginBasePath('/Filament/Resources'), for: 'Webkul\\Payment\\Filament\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Pages'), for: 'Webkul\\Payment\\Filament\\Pages')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Clusters'), for: 'Webkul\\Payment\\Filament\\Clusters')
                    ->discoverWidgets(in: $this->getPluginBasePath('/Filament/Widgets'), for: 'Webkul\\Payment\\Filament\\Widgets');
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
