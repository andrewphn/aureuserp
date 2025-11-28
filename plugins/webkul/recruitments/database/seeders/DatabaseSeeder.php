<?php

namespace Webkul\Recruitment\Database\Seeders;

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
            StageSeeder::class,
            DegreeSeeder::class,
            RefuseReasonSeeder::class,
            ApplicantCategorySeeder::class,
        ]);
    }
}
