<?php

namespace Webkul\Project\Database\Seeders;

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
            ProjectStageSeeder::class,
            TaskSettingsSeeder::class,
        ]);
    }
}
