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
                'phone' => null,
                'street1' => '15 Correia Ln',
                'street2' => null,
                'city' => 'Nantucket',
                'state_id' => null, // Massachusetts
                'country_id' => 233, // USA
                'zip' => '02554',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✓ Created Trottier Fine Woodworking as customer');
        } else {
            $this->command->info('✓ Trottier Fine Woodworking already exists');
        }
    }
}
