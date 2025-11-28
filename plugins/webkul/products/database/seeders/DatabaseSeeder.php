<?php

namespace Webkul\Product\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Database Seeder database seeder
 *
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PriceListSeeder::class,
            ProductCategorySeeder::class,
            ProductCombinationSeeder::class,
        ]);
    }
}
