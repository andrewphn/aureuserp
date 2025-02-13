<?php

namespace Webkul\Purchase;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

class PurchaseServiceProvider extends PackageServiceProvider
{
    public static string $name = 'purchases';

    public static string $viewNamespace = 'purchases';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2025_02_11_101100_create_purchases_order_groups_table',
                '2025_02_11_101102_create_purchases_orders_table',
                '2025_02_11_101118_create_purchases_order_lines_table',
                '2025_02_11_101152_create_purchases_requisitions_table',
                '2025_02_11_101233_create_purchases_requisition_lines_table',
            ])
            ->runsMigrations()
            ->hasSettings([
            ])
            ->runsSettings()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->startWith(function(InstallCommand $command) {
                        $command->call('products:install');
                    })
                    ->endWith(function (InstallCommand $command) {
                        if ($command->confirm('Would you like to seed the data now?')) {
                            $command->comment('Seeding data...');

                            $command->call('db:seed', [
                                '--class' => 'Webkul\\Purchase\\Database\Seeders\\DatabaseSeeder',
                            ]);
                        }
                    })
                    ->askToStarRepoOnGitHub('aureuserp/purchases');
            });
    }

    public function packageBooted(): void
    {
        //
    }
}
