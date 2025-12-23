<?php

namespace Webkul\TcsCms;

use Illuminate\Support\Facades\Route;
use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;
use Webkul\TcsCms\Console\Commands\MigrateTcsContentCommand;

/**
 * TCS CMS Service Provider
 */
class TcsCmsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'tcs-cms';

    public static string $viewNamespace = 'tcs-cms';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2025_12_21_000001_create_tcs_cms_pages_table',
                '2025_12_21_000002_create_tcs_cms_page_sections_table',
                '2025_12_21_000003_create_tcs_home_sections_table',
                '2025_12_21_000004_create_tcs_portfolio_projects_table',
                '2025_12_21_000005_create_tcs_journals_table',
                '2025_12_21_000006_create_tcs_services_table',
                '2025_12_21_000007_create_tcs_materials_table',
                '2025_12_21_000008_create_tcs_faqs_table',
                '2025_12_21_000009_create_tcs_team_members_table',
                '2025_12_21_100001_add_missing_fields_to_tcs_tables',
            ])
            ->runsMigrations()
            ->hasSettings([])
            ->runsSettings()
            ->hasDependencies([
                'website',
            ])
            ->hasCommands([
                MigrateTcsContentCommand::class,
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
        // Load public routes for TCS Website
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
