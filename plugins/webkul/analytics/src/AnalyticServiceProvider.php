<?php

namespace Webkul\Analytic;

use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

/**
 * Analytic Service Provider service provider
 *
 */
class AnalyticServiceProvider extends PackageServiceProvider
{
    public static string $name = 'analytics';

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
            ->hasTranslations()
            ->hasMigrations([
                '2024_12_18_131844_create_analytic_records_table',
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
        //
    }
}
