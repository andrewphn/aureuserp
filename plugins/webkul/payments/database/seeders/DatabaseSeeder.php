<?php

namespace Webkul\Payment\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Database Seeder database seeder
 *
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PaymentSeeder::class,
        ]);
    }
}
