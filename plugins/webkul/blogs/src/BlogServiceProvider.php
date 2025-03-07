<?php

namespace Webkul\Blog;

use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;
use Livewire\Livewire;
use Webkul\Blog\Livewire\Customer\ListBlogs;

class BlogServiceProvider extends PackageServiceProvider
{
    public static string $name = 'blogs';

    public static string $viewNamespace = 'blogs';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2025_03_06_093011_create_blogs_categories_table',
                '2025_03_06_094011_create_blogs_blogs_table',
            ])
            ->runsMigrations()
            ->hasSettings([
            ])
            ->runsSettings()
            ->hasDependencies([
                'website',
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->installDependencies()
                    ->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('blogs', __DIR__ . '/../resources/dist/blogs.css'),
        ], 'blogs');
        
        Livewire::component('list-blogs', ListBlogs::class);
    }
}
