<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrottierFineWoodworkingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if Trottier Fine Woodworking already exists
        $exists = DB::table('partners_partners')
            ->where('name', 'Trottier Fine Woodworking')
            ->exists();

        if (!$exists) {
            DB::table('partners_partners')->insert([
                'name' => 'Trottier Fine Woodworking',
                'sub_type' => 'customer',
                'account_type' => 'company',
                'email' => 'contact@trottierfine.com',
                'phone' => '(555) 123-4567',
                'street1' => '123 Main Street',
                'city' => 'Burlington',
                'state_id' => null, // Set appropriate state ID if needed
                'country_id' => 233, // USA country ID (adjust if needed)
                'zip' => '05401',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✓ Created Trottier Fine Woodworking as customer');
        } else {
            $this->command->info('✓ Trottier Fine Woodworking already exists');
        }
    }
}
