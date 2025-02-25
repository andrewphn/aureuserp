<?php

namespace Webkul\Account\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('accounts_tax_groups')->delete();

        $now = Carbon::now();

        $taxGroups = [
            [
                'sort'               => 1,
                'company_id'         => 1,
                'country_id'         => 104,
                'creator_id'         => 1,
                'name'               => 'Tax 15%',
                'preceding_subtotal' => null,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ];

        DB::table('accounts_tax_groups')->insert($taxGroups);
    }
}
