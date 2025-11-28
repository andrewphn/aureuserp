<?php

namespace Webkul\Partner\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Database Seeder database seeder
 *
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        $this->call(IndustrySeeder::class);

        $this->call(TitleSeeder::class);

        $this->call(VendorSeeder::class);
    }
}
