<?php

namespace Webkul\Website\Database\Seeders;

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
        $this->call([
            WebsitePageSeeder::class,
        ]);
    }
}
