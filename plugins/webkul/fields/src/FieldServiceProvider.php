<?php

namespace Webkul\Field;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Gate;
use Webkul\Field\Models\Field;
use Webkul\Field\Policies\FieldPolicy;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

/**
 * Field Service Provider service provider
 *
 */
class FieldServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fields';

    public static string $viewNamespace = 'fields';

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
                '2024_11_13_052541_create_custom_fields_table',
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

        Gate::policy(Field::class, FieldPolicy::class);
    }

    /**
     * Register Custom Css
     *
     */
    public function registerCustomCss()
    {
        FilamentAsset::register([
            Css::make('fields', __DIR__.'/../resources/dist/fields.css'),
        ], 'fields');
    }
}
