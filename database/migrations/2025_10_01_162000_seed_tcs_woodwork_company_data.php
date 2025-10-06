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
        // Get the United States country ID
        $countryId = DB::table('countries')->where('name', 'United States')->value('id');

        // Get the New York state ID
        $stateId = DB::table('states')->where('name', 'New York')->value('id');

        // Get the United States dollar currency ID
        $currencyId = DB::table('currencies')->where('name', 'United States dollar')->value('id');

        // Update the first company (ID 1) with TCS Woodwork data
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
                'state_id' => $stateId,
                'country_id' => $countryId,
                'currency_id' => $currencyId,
                'is_active' => true,
                'founded_date' => '2000-01-01',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore DummyCorp data
        $currencyId = DB::table('currencies')->where('name', 'United States dollar')->value('id');

        DB::table('companies')
            ->where('id', 1)
            ->update([
                'name' => 'DummyCorp LLC',
                'company_id' => 'DUMCOMP001',
                'tax_id' => 'DUM123456',
                'registration_number' => 'DUMREG789',
                'email' => 'dummy@dummycorp.local',
                'phone' => '1234567890',
                'mobile' => '1234567890',
                'website' => 'http://dummycorp.local',
                'street1' => null,
                'street2' => null,
                'city' => null,
                'zip' => null,
                'state_id' => null,
                'country_id' => null,
                'currency_id' => $currencyId,
                'is_active' => true,
                'founded_date' => '2000-01-01',
                'updated_at' => now(),
            ]);
    }
};
