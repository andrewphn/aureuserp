<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Trottier Fine Woodworking Seeder database seeder
 *
 */
class TrottierFineWoodworkingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert or update Trottier Fine Woodworking
        DB::table('partners_partners')->updateOrInsert(
            ['name' => 'Trottier Fine Woodworking'],
            [
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
                'updated_at' => now(),
            ]
        );

        $this->command->info('✓ Trottier Fine Woodworking created/updated with Nantucket address');
    }
}
