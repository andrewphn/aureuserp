<?php

namespace Webkul\Chatter;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Webkul\Chatter\Livewire\ChatterPanel;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

/**
 * Chatter Service Provider service provider
 *
 */
class ChatterServiceProvider extends PackageServiceProvider
{
    public static string $name = 'chatter';

    public static string $viewNamespace = 'chatter';

    /**
     * Configure Custom Package
     *
     * @param Package $package
     * @return void
     */
    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->isCore()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2024_12_11_101222_create_chatter_followers_table',
                '2024_12_23_062355_create_chatter_messages_table',
                '2024_12_23_080148_create_chatter_attachments_table',
                '2025_03_12_072356_add_column_is_read_to_chatter_messages_table',
            ])
            ->runsMigrations();
    }

    /**
     * Package Booted
     *
     * @return void
     */
    public function packageBooted(): void
    {
        $this->registerCustomCss();
        $this->registerLivewireComponents();
    }

    /**
     * Register Livewire Components
     *
     * @return void
     */
    protected function registerLivewireComponents(): void
    {
        // Register Livewire component for FilamentPHP v4
        Livewire::component('chatter-panel', ChatterPanel::class);

        // Also register with full namespace for v4 compatibility
        Livewire::component('webkul.chatter.chatter-panel', ChatterPanel::class);
    }

    /**
     * Register Custom Css
     *
     */
    public function registerCustomCss()
    {
        FilamentAsset::register([
            Css::make('chatter', __DIR__.'/../resources/dist/chatter.css'),
        ], 'chatter');
    }
}
