<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Tcs Work Location Seeder database seeder
 *
 */
class TcsWorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing work locations
        DB::table('employees_work_locations')->delete();

        $user = User::first();
        $tcsCompany = Company::where('name', 'TCS Woodwork')->first();

        if (!$tcsCompany) {
            $this->command->error('TCS Woodwork company not found!');
            return;
        }

        $workLocations = [
            [
                'name' => 'TCS Woodwork Shop',
                'location_type' => 'shop',
                'location_number' => '001',
                'latitude' => 41.51853219666564,
                'longitude' => -74.0079441641726,
                'is_active' => true,
                'company_id' => $tcsCompany->id,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('employees_work_locations')->insert($workLocations);

        $this->command->info('TCS Woodwork shop location created successfully!');
    }
}
