<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create or find the TCS partner record
        $tcsPartner = DB::table('partners_partners')
            ->where('name', 'The Carpenter\'s Son Woodworking LLC')
            ->where('sub_type', 'company')
            ->first();

        if (!$tcsPartner) {
            // Get state/country IDs
            $nyState = DB::table('states')->where('code', 'NY')->first();
            $usCountry = DB::table('countries')->where('code', 'US')->first();

            $tcsPartnerId = DB::table('partners_partners')->insertGetId([
                'name'       => 'The Carpenter\'s Son Woodworking LLC',
                'email'      => 'info@tcswoodwork.com',
                'phone'      => '(845) 816-2388',
                'street1'    => '392 N Montgomery St',
                'street2'    => 'Building B',
                'city'       => 'Newburgh',
                'zip'        => '12550',
                'state_id'   => $nyState?->id,
                'country_id' => $usCountry?->id,
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $tcsPartnerId = $tcsPartner->id;
        }

        // Fix company ID 1 to be "The Carpenter's Son Woodworking LLC" with full address
        DB::table('companies')
            ->where('id', 1)
            ->update([
                'name' => 'The Carpenter\'s Son Woodworking LLC',
                'acronym' => 'TCS',
                'street1' => '392 N Montgomery St',
                'street2' => 'Building B',
                'city' => 'Newburgh',
                'state_id' => 35, // New York
                'country_id' => 233, // United States
                'zip' => '12550',
                'phone' => '(845) 816-2388',
                'email' => 'info@tcswoodwork.com',
                'partner_id' => $tcsPartnerId,
                'updated_at' => now(),
            ]);

        // Update Trottier to be "Trottier's Son" and set parent_id to TCS
        $tcsId = DB::table('companies')->where('acronym', 'TCS')->value('id');
        
        DB::table('companies')
            ->where('email', 'jeremybtrottier@gmail.com')
            ->update([
                'name' => 'Trottier\'s Son',
                'parent_id' => $tcsId,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old names
        DB::table('companies')
            ->where('id', 1)
            ->update([
                'name' => 'TCS Woodwork',
                'updated_at' => now(),
            ]);

        DB::table('companies')
            ->where('email', 'jeremybtrottier@gmail.com')
            ->update([
                'name' => 'Trottier Fine Woodworking',
                'parent_id' => null,
                'updated_at' => now(),
            ]);
    }
};
