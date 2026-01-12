<?php

namespace Webkul\Support\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * State Seeder database seeder
 *
 */
class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = base_path('plugins/webkul/security/src/Data/states.json');

        if (File::exists($path)) {
            $states = json_decode(File::get($path), true);

            foreach ($states as $state) {
                $countryId = (int) ($state['country_id'] ?? null);
                $name = (string) ($state['name'] ?? null);
                $code = (string) ($state['code'] ?? null);

                // Check if state already exists (by name + country_id or code + country_id)
                $exists = DB::table('states')
                    ->where('country_id', $countryId)
                    ->where(function ($query) use ($name, $code) {
                        $query->where('name', $name)
                              ->orWhere('code', $code);
                    })
                    ->exists();

                // Only insert if it doesn't exist
                if (!$exists) {
                    DB::table('states')->insert([
                        'country_id' => $countryId,
                        'name'       => $name,
                        'code'       => $code,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
