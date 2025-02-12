<?php

namespace Webkul\Support\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\State;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('companies')->delete();
        DB::table('partners_partners')->delete();
        DB::table('company_addresses')->delete();

        $currencyId = Currency::inRandomOrder()->first()?->id;
        $stateId = State::inRandomOrder()->first()->id;
        $countryId = Country::inRandomOrder()->first()->id;
        $user = User::first();

        $now = now();

        $companyId = DB::table('companies')->insertGetId([
            'sort'                => 1,
            'name'                => 'DummyCorp LLC',
            'tax_id'              => 'DUM123456',
            'registration_number' => 'DUMREG789',
            'company_id'          => 'DUMCOMP001',
            'creator_id'          => $user?->id,
            'email'               => 'dummy@dummycorp.local',
            'phone'               => '1234567890',
            'mobile'              => '1234567890',
            'color'               => '#AAAAAA',
            'is_active'           => true,
            'founded_date'        => '2000-01-01',
            'currency_id'         => $currencyId,
            'website'             => 'http://dummycorp.local',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        $partnerId = DB::table('partners_partners')->insertGetId([
            'sub_type'         => 'company',
            'company_registry' => 'DUMREG780',
            'name'             => 'DummyCorp LLC',
            'email'            => 'dummy@dummycorp.local',
            'website'          => 'http://dummycorp.local',
            'tax_id'           => 'DUM123456',
            'phone'            => '1234567890',
            'mobile'           => '1234567890',
            'creator_id'       => $user?->id,
            'color'            => '#AAAAAA',
            'company_id'       => $companyId,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $partnerPermanentAddressId = DB::table('partners_addresses')->insertGetId([
            'partner_id' => $partnerId,
            'street1'    => '123 Placeholder Ave',
            'city'       => 'Ave',
            'name'       => 'DummyCorp LLC',
            'type'       => 'permanent',
            'state_id'   => $stateId,
            'country_id' => Country::inRandomOrder()->first()->id,
            'zip'        => '000000',
            'creator_id' => $user?->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('partners_addresses')->insertGetId([
            'partner_id' => $partnerId,
            'street1'    => '123 Placeholder Ave',
            'city'       => 'Ave',
            'name'       => 'DummyCorp LLC',
            'type'       => 'present',
            'creator_id' => $user?->id,
            'state_id'   => $stateId,
            'country_id' => $countryId,
            'zip'        => '000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('company_addresses')->insert([
            'partner_address_id' => $partnerPermanentAddressId,
            'company_id'         => $companyId,
            'street1'            => '123 Placeholder Ave',
            'city'               => 'Ave',
            'state_id'           => State::inRandomOrder()->first()->id,
            'country_id'         => Country::inRandomOrder()->first()->id,
            'zip'                => '000000',
            'is_primary'         => true,
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);
    }
}
