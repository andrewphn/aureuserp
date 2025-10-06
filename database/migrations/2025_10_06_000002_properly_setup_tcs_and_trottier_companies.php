<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get necessary IDs
        $usCountryId = DB::table('countries')->where('name', 'United States')->value('id');
        $nyStateId = DB::table('states')->where('name', 'New York')->value('id');
        $maStateId = DB::table('states')->where('name', 'Massachusetts')->value('id');
        $usdCurrencyId = DB::table('currencies')->where('name', 'United States dollar')->value('id');
        $creatorId = DB::table('users')->first()->id ?? null;
        
        // Fix company ID 1 to be The Carpenter's Son Woodworking LLC
        DB::table('companies')
            ->where('id', 1)
            ->update([
                'name' => 'The Carpenter\'s Son Woodworking LLC',
                'acronym' => 'TCS',
                'company_id' => 'TCSWOOD001',
                'tax_id' => 'TCS123456',
                'registration_number' => 'TCSREG789',
                'email' => 'info@tcswoodwork.com',
                'phone' => '(845) 816-2388',
                'website' => 'https://tcswoodwork.com',
                'street1' => '392 N Montgomery St',
                'street2' => 'Building B',
                'city' => 'Newburgh',
                'zip' => '12550',
                'state_id' => $nyStateId,
                'country_id' => $usCountryId,
                'currency_id' => $usdCurrencyId,
                'parent_id' => null,
                'is_active' => true,
                'updated_at' => now(),
            ]);
        
        // Check if Trottier's Son already exists
        $trottierId = DB::table('companies')->where('company_id', 'TROTWOOD001')->value('id');
        
        if (!$trottierId) {
            // Get or create partner for Trottier
            $partnerId = DB::table('partners_partners')
                ->where('email', 'jeremybtrottier@gmail.com')
                ->value('id');
                
            if (!$partnerId) {
                $partnerId = DB::table('partners_partners')->insertGetId([
                    'name' => 'Trottier Fine Woodworking',
                    'email' => 'jeremybtrottier@gmail.com',
                    'phone' => '(508) 332-8671',
                    'street1' => '15B Correia Lane',
                    'city' => 'Nantucket',
                    'zip' => '02554',
                    'state_id' => $maStateId,
                    'country_id' => $usCountryId,
                    'creator_id' => $creatorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Create Trottier's Son as a branch of TCS
            DB::table('companies')->insert([
                'name' => 'Trottier\'s Son',
                'acronym' => 'TFW',
                'company_id' => 'TROTWOOD001',
                'parent_id' => 1, // TCS company ID
                'partner_id' => $partnerId,
                'email' => 'jeremybtrottier@gmail.com',
                'phone' => '(508) 332-8671',
                'street1' => '15B Correia Lane',
                'city' => 'Nantucket',
                'zip' => '02554',
                'state_id' => $maStateId,
                'country_id' => $usCountryId,
                'currency_id' => $usdCurrencyId,
                'creator_id' => $creatorId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Trottier's Son if it exists
        DB::table('companies')
            ->where('company_id', 'TROTWOOD001')
            ->delete();
    }
};
