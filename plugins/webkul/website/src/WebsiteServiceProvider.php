<?php

namespace Webkul\Website;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;
use Webkul\Website\Http\Responses\LogoutResponse;

/**
 * Website Service Provider service provider
 *
 */
class WebsiteServiceProvider extends PackageServiceProvider
{
    public static string $name = 'website';

    public static string $viewNamespace = 'website';

    /**
     * Configure Custom Package
     *
     * @param Package $package
     * @return void
     */
    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2025_03_10_094011_create_website_pages_table',
                '2025_03_10_064655_alter_partners_partners_table',
            ])
            ->runsMigrations()
            ->hasSeeder('Webkul\\Website\\Database\Seeders\\DatabaseSeeder')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->installDependencies()
                    ->runsMigrations()
                    ->runsSeeders();
            })
            ->hasSettings([
                '2025_03_10_094021_create_website_contact_settings',
            ])
            ->runsSettings()
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    /**
     * Package Booted
     *
     * @return void
     */
    public function packageBooted(): void
    {
        $this->registerCustomCss();

        if (! Package::isPluginInstalled(self::$name)) {
            Route::get('/', function () {
                return redirect()->route('filament.admin.auth.login');
            });
        }
    }

    /**
     * Package Registered
     *
     * @return void
     */
    public function packageRegistered(): void
    {
        $this->app->bind(\Filament\Auth\Http\Responses\Contracts\LogoutResponse::class, LogoutResponse::class);
    }

    /**
     * Register Custom Css
     *
     */
    public function registerCustomCss()
    {
        FilamentAsset::register([
            Css::make('website', __DIR__.'/../resources/dist/website.css'),
        ], 'website');
    }
}
