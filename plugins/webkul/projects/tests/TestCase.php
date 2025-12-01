<?php

namespace Webkul\Project\Tests;

use Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

/**
 * Test Case class
 *
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Perform any work that should take place once the database has been setup.
     * Run plugin migrations after the main migrations in correct dependency order.
     */
    protected function afterRefreshingDatabase(): void
    {
        // Plugin migrations need to run in specific order due to dependencies
        $pluginPaths = [
            'plugins/webkul/partners/database/migrations',      // Partners first (no deps)
            'plugins/webkul/products/database/migrations',      // Products (required by sales)
            'plugins/webkul/inventories/database/migrations',   // Inventories
            'plugins/webkul/accounts/database/migrations',      // Accounts
            'plugins/webkul/sales/database/migrations',         // Sales (depends on products)
            'plugins/webkul/projects/database/migrations',      // Projects last (depends on sales)
        ];

        foreach ($pluginPaths as $path) {
            if (is_dir(base_path($path))) {
                Artisan::call('migrate', [
                    '--path' => $path,
                    '--force' => true,
                ]);
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Seed essential data required for tests
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Webkul\Security\Database\Seeders\DatabaseSeeder::class);
        $this->seed(\Webkul\Support\Database\Seeders\DatabaseSeeder::class);

        // Create admin user for foreign key constraints
        \App\Models\User::factory()->create([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@test.com',
        ]);
    }
}
