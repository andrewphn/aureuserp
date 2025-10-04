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

        // Get the Massachusetts state ID
        $stateId = DB::table('states')->where('name', 'Massachusetts')->value('id');

        // Get the United States dollar currency ID
        $currencyId = DB::table('currencies')->where('name', 'United States dollar')->value('id');

        // Get the first user ID for creator (nullable - will be set later when users exist)
        $creatorId = DB::table('users')->first()->id ?? null;

        // Create a partner for Trottier Fine Woodworking first
        $partnerId = DB::table('partners_partners')->insertGetId([
            'name' => 'Trottier Fine Woodworking',
            'email' => 'jeremybtrottier@gmail.com',
            'phone' => '(508) 332-8671',
            'street1' => '15B Correia Lane',
            'city' => 'Nantucket',
            'zip' => '02554',
            'state_id' => $stateId,
            'country_id' => $countryId,
            'creator_id' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert Trottier Fine Woodworking company
        DB::table('companies')->insert([
            'name' => 'Trottier Fine Woodworking',
            'acronym' => 'TFW',
            'company_id' => 'TROTWOOD001',
            'partner_id' => $partnerId,
            'email' => 'jeremybtrottier@gmail.com',
            'phone' => '(508) 332-8671',
            'street1' => '15B Correia Lane',
            'city' => 'Nantucket',
            'zip' => '02554',
            'state_id' => $stateId,
            'country_id' => $countryId,
            'currency_id' => $currencyId,
            'creator_id' => $creatorId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Trottier Fine Woodworking company
        DB::table('companies')
            ->where('company_id', 'TROTWOOD001')
            ->delete();

        // Remove Trottier Fine Woodworking partner
        DB::table('partners_partners')
            ->where('name', 'Trottier Fine Woodworking')
            ->where('email', 'jeremybtrottier@gmail.com')
            ->delete();
    }
};
