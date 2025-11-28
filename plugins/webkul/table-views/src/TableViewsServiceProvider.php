<?php

namespace Webkul\TableViews;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

/**
 * Table Views Service Provider service provider
 *
 */
class TableViewsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'table-views';

    public static string $viewNamespace = 'table-views';

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
                '2024_11_19_142134_create_table_views_table',
                '2024_11_21_142134_create_table_view_favorites_table',
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
        $this->registerCustomJs();
    }

    /**
     * Package Registered
     *
     */
    public function packageRegistered()
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE,
            fn (): View => view('table-views::filament.resources.pages.list-records.favorites-views'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::RESOURCE_PAGES_MANAGE_RELATED_RECORDS_TABLE_BEFORE,
            fn (): View => view('table-views::filament.resources.pages.list-records.favorites-views'),
        );
    }

    /**
     * Register Custom Css
     *
     */
    public function registerCustomCss()
    {
        FilamentAsset::register([
            Css::make('table-views', __DIR__.'/../resources/dist/table-views.css'),
        ], 'table-views');
    }

    /**
     * Register Custom Js
     *
     */
    public function registerCustomJs()
    {
        FilamentAsset::register([
            Js::make('table-view-persistence', __DIR__.'/../resources/js/table-view-persistence.js'),
        ], 'table-views');
    }
}
