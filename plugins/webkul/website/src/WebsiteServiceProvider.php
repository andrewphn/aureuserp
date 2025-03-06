<?php

namespace Webkul\Website;

use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Webkul\Support\PackageServiceProvider;
use Webkul\Support\Package;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

class WebsiteServiceProvider extends PackageServiceProvider
{
    public static string $name = 'website';

    public static string $viewNamespace = 'website';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {})
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('website', __DIR__ . '/../resources/dist/website.css'),
        ], 'website');
    }

    public function packageRegistered(): void
    {
    }
}
