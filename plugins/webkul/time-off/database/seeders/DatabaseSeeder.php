<?php

namespace Webkul\TimeOff\Database\Seeders;

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
            AccrualPlanSeeder::class,
            LeaveTypeSeeder::class,
            LeaveMandatoryDay::class,
            LeaveTypeSeeder::class,
        ]);
    }
}
