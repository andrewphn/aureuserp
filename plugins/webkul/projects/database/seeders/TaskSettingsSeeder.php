<?php

namespace Webkul\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['group' => 'task', 'name' => 'enable_recurring_tasks', 'locked' => 0, 'payload' => '"1"'],
            ['group' => 'task', 'name' => 'enable_task_dependencies', 'locked' => 0, 'payload' => '"1"'],
            ['group' => 'task', 'name' => 'enable_project_stages', 'locked' => 0, 'payload' => '"1"'],
            ['group' => 'task', 'name' => 'enable_milestones', 'locked' => 0, 'payload' => '"1"'],
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
