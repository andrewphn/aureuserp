<?php

namespace Webkul\Product;

use Webkul\Support\Console\Commands\InstallCommand;
use Webkul\Support\Console\Commands\UninstallCommand;
use Webkul\Support\Package;
use Webkul\Support\PackageServiceProvider;

class ProductServiceProvider extends PackageServiceProvider
{
    public static string $name = 'products';

    public static string $viewNamespace = 'products';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2025_01_05_063925_create_products_categories_table',
                '2025_01_05_100751_create_products_products_table',
                '2025_01_05_100830_create_products_tags_table',
                '2025_01_05_100832_create_products_product_tag_table',
                '2025_01_05_104456_create_products_attributes_table',
                '2025_01_05_104512_create_products_attribute_options_table',
                '2025_01_05_104759_create_products_product_attributes_table',
                '2025_01_05_104809_create_products_product_attribute_values_table',
                '2025_01_05_105626_create_products_packagings_table',
                '2025_01_05_113357_create_products_price_rules_table',
                '2025_01_05_113402_create_products_price_rule_items_table',
                '2025_01_05_123412_create_products_product_suppliers_table',
                '2025_02_18_112837_create_products_product_price_lists_table',
                '2025_02_21_053249 _create_products_product_combinations_table',
                '2025_07_28_080116_alter_products_products_table',
                '2025_09_30_154900_seed_tcs_product_categories',
                '2025_09_30_160000_seed_tcs_vendors_and_prices',
                '2025_09_30_160500_fix_vendor_price_currencies',
                '2025_09_30_162300_seed_product_tags',
                '2025_09_30_163000_add_vendor_url_to_product_suppliers',
                '2025_09_30_164000_cleanup_test_vendor_pricing',
                '2025_09_30_166000_import_amazon_products',
                '2025_09_30_167000_import_migration_products',
                '2025_09_30_168000_validate_product_import',
                '2025_09_30_181047_restructure_products_with_variants_and_brands',
                '2025_10_01_170000_create_product_attributes',
                '2025_10_01_172000_import_amazon_products_with_variants',
                '2025_10_01_173000_import_inventory_products_with_variants_and_quantities',
            ])
            ->hasSeeder('Webkul\\Product\\Database\Seeders\\DatabaseSeeder')
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->runsMigrations()
                    ->runsSeeders();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    public function packageBooted(): void
    {
        //
    }
}
