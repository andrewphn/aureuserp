<?php

namespace Webkul\Blog;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\Support\Package;

class BlogPlugin implements Plugin
{
    public function getId(): string
    {
        return 'blogs';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel
            ->when($panel->getId() == 'front', function (Panel $panel) {
                $panel
                    ->discoverResources(in: $this->getPluginBasePath('/Filament/Front/Resources'), for: 'Webkul\\Blog\\Filament\\Front\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Front/Pages'), for: 'Webkul\\Blog\\Filament\\Front\\Pages')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Front/Clusters'), for: 'Webkul\\Blog\\Filament\\Front\\Clusters')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Front/Widgets'), for: 'Webkul\\Blog\\Filament\\Front\\Widgets');
            })
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel
                    ->discoverResources(in: $this->getPluginBasePath('/Filament/Admin/Resources'), for: 'Webkul\\Blog\\Filament\\Admin\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Admin/Pages'), for: 'Webkul\\Blog\\Filament\\Admin\\Pages')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Admin/Clusters'), for: 'Webkul\\Blog\\Filament\\Admin\\Clusters')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Admin/Widgets'), for: 'Webkul\\Blog\\Filament\\Admin\\Widgets');
            });
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected function getPluginBasePath($path = null): string
    {
        $reflector = new \ReflectionClass(get_class($this));

        return dirname($reflector->getFileName()).($path ?? '');
    }
}
