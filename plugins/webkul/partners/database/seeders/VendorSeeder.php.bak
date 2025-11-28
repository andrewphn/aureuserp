<?php

namespace Webkul\Partner\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorSeeder extends Seeder
{
    /**
     * Seed TCS vendor partners and their pricing
     */
    public function run(): void
    {
        $userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id');

        // Get country IDs
        $usaCountryId = DB::table('countries')->where('code', 'US')->value('id');
        $canadaCountryId = DB::table('countries')->where('code', 'CA')->value('id');

        // Get state IDs
        $stateIds = [
            'CA' => DB::table('states')->where('code', 'CA')->where('country_id', $usaCountryId)->value('id'),
            'NY' => DB::table('states')->where('code', 'NY')->where('country_id', $usaCountryId)->value('id'),
            'DE' => DB::table('states')->where('code', 'DE')->where('country_id', $usaCountryId)->value('id'),
            'IN' => DB::table('states')->where('code', 'IN')->where('country_id', $usaCountryId)->value('id'),
        ];

        $vendors = [
            [
                'name' => 'Richelieu Hardware',
                'sub_type' => 'supplier',
                'email' => '[email protected]',
                'phone' => '1-800-361-6000',
                'website' => 'www.richelieu.com',
                'street1' => '7900 Henri-Bourassa West',
                'city' => 'Ville Saint-Laurent',
                'state_id' => null,
                'zip' => 'H4S 1V4',
                'country_id' => $canadaCountryId,
            ],
            [
                'name' => 'Serious Grit',
                'sub_type' => 'supplier',
                'email' => 'help@seriousgrit.com',
                'phone' => '(760) 279-3767',
                'website' => 'www.seriousgrit.com',
                'street1' => '2796 Loker Ave W #105',
                'city' => 'Carlsbad',
                'state_id' => $stateIds['CA'],
                'zip' => '92010',
                'country_id' => $usaCountryId,
            ],
            [
                'name' => 'Wurth Wood Group',
                'sub_type' => 'supplier',
                'email' => '[email protected]',
                'phone' => '(800) 289-8788',
                'website' => 'www.wurthwoodgroup.com',
                'street1' => '1 Wurth Way',
                'city' => 'Fishers',
                'state_id' => $stateIds['IN'],
                'zip' => '46038',
                'country_id' => $usaCountryId,
            ],
            [
                'name' => 'Adams & Kennedy',
                'sub_type' => 'supplier',
                'email' => '[email protected]',
                'phone' => '(613) 233-5138',
                'website' => 'www.adamsandkennedy.com',
                'street1' => '6178 Mitch Owen Road',
                'city' => 'Greely',
                'state_id' => null,
                'zip' => 'K4P 1N1',
                'country_id' => $canadaCountryId,
            ],
        ];

        foreach ($vendors as $vendor) {
            DB::table('partners_partners')->insert([
                'account_type' => 'company',
                'sub_type' => $vendor['sub_type'],
                'name' => $vendor['name'],
                'email' => $vendor['email'],
                'phone' => $vendor['phone'],
                'website' => $vendor['website'],
                'street1' => $vendor['street1'],
                'city' => $vendor['city'],
                'state_id' => $vendor['state_id'],
                'zip' => $vendor['zip'],
                'country_id' => $vendor['country_id'],
                'creator_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
