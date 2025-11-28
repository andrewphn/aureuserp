<?php

namespace Webkul\Employee\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Work Location Seeder database seeder
 *
 */
class WorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();

        // Skip seeding if no company exists yet (erp:install creates the company)
        if (!$company) {
            return;
        }

        DB::table('employees_work_locations')->delete();

        $user = User::first();

        $workLocations = [
            [
                'name'               => 'Home',
                'company_id'         => $company?->id,
                'location_type'      => 'home',
                'is_active'          => 1,
                'creator_id'         => $user?->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Building 1, Second Floor',
                'company_id'         => $company?->id,
                'location_type'      => 'office',
                'is_active'          => 1,
                'creator_id'         => $user?->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Other',
                'company_id'         => $company?->id,
                'location_type'      => 'other',
                'is_active'          => 1,
                'creator_id'         => $user?->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ];

        DB::table('employees_work_locations')->insert($workLocations);
    }
}
