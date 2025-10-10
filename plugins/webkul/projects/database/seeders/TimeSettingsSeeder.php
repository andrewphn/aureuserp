<?php

namespace Webkul\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['group' => 'time', 'name' => 'enable_timesheets', 'locked' => 0, 'payload' => '"1"'],
        ];

        foreach ($settings as $setting) {
            // Only insert if not exists
            $exists = DB::table('settings')
                ->where('group', $setting['group'])
                ->where('name', $setting['name'])
                ->exists();

            if (!$exists) {
                DB::table('settings')->insert($setting);
            }
        }
    }
}
