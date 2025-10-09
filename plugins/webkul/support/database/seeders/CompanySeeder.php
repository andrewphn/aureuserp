<?php

namespace Webkul\Support\Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Currency;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip if companies already exist (seeded by migrations)
        if (DB::table('companies')->count() > 0) {
            return;
        }

        DB::beginTransaction();

        try {
            if (
                ! Schema::hasTable('users')
                || ! Schema::hasTable('companies')
                || ! Schema::hasTable('partners_partners')
                || ! Schema::hasTable('currencies')
                || ! Schema::hasTable('countries')
                || ! Schema::hasTable('states')
            ) {
                throw new Exception('Required tables are missing.');
            }

            $user = User::first();
            $usdCurrency = DB::table('currencies')->where('code', 'USD')->first();
            $usCountry = DB::table('countries')->where('code', 'US')->first();
            $nyState = DB::table('states')->where('code', 'NY')->first();
            $maState = DB::table('states')->where('code', 'MA')->first();

            if (!$usdCurrency || !$usCountry || !$nyState || !$maState) {
                throw new Exception('Required reference data (currency, country, states) not found.');
            }

            // Create TCS company (no partner needed, it's the main company)
            $tcsData = [
                'id'                  => 1,
                'sort'                => 1,
                'name'                => 'The Carpenter\'s Son Woodworking LLC',
                'acronym'             => 'TCS',
                'company_id'          => 'TCSWOOD001',
                'tax_id'              => 'TCS123456',
                'registration_number' => 'TCSREG789',
                'email'               => 'info@tcswoodwork.com',
                'phone'               => '(845) 816-2388',
                'website'             => 'https://tcswoodwork.com',
                'street1'             => '392 N Montgomery St',
                'street2'             => 'Building B',
                'city'                => 'Newburgh',
                'zip'                 => '12550',
                'state_id'            => $nyState->id,
                'country_id'          => $usCountry->id,
                'currency_id'         => $usdCurrency->id,
                'parent_id'           => null,
                'is_active'           => true,
                'is_default'          => true,
                'created_at'          => now(),
                'updated_at'          => now(),
            ];

            // Only set creator_id if user exists
            if ($user) {
                $tcsData['creator_id'] = $user->id;
            }

            DB::table('companies')->insert($tcsData);

            // Create Trottier partner (customer)
            $trottierId = DB::table('partners_partners')->insertGetId([
                'name'       => 'Trottier Fine Woodworking',
                'email'      => 'jeremybtrottier@gmail.com',
                'phone'      => '(508) 332-8671',
                'street1'    => '15B Correia Lane',
                'city'       => 'Nantucket',
                'zip'        => '02554',
                'state_id'   => $maState->id,
                'country_id' => $usCountry->id,
                'sub_type'   => 'customer',
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create Trottier company (branch of TCS)
            DB::table('companies')->insert([
                'name'       => 'Trottier Fine Woodworking',
                'acronym'    => 'TFW',
                'company_id' => 'TFWWOOD001',
                'parent_id'  => 1,
                'partner_id' => $trottierId,
                'email'      => 'jeremybtrottier@gmail.com',
                'phone'      => '(508) 332-8671',
                'street1'    => '15B Correia Lane',
                'city'       => 'Nantucket',
                'zip'        => '02554',
                'state_id'   => $maState->id,
                'country_id' => $usCountry->id,
                'currency_id' => $usdCurrency->id,
                'creator_id' => $user?->id,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            echo "❌ Company seeder error: " . $e->getMessage() . PHP_EOL;
            echo "   File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
            throw $e;
        }
    }
}
