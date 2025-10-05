<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class TcsWorkScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $tcsCompany = Company::where('acronym', 'TCS')->first();

        if (!$tcsCompany) {
            $this->command->error('TCS company not found!');
            return;
        }

        // Update TCS company with capacity data
        // Based on 234 LF/month capacity: 234 ÷ 17 working days = 13.76 LF/day
        $tcsCompany->update([
            'shop_capacity_per_day' => 13.76,  // Will auto-calculate hourly (1.72 LF/hr) and monthly (234 LF/mo)
            'working_hours_per_day' => 8,
            'working_days_per_month' => 17,
        ]);

        // Delete existing TCS Woodwork calendar and its attendances
        $existingCalendar = DB::table('employees_calendars')
            ->where('name', 'TCS Woodwork Shop Schedule')
            ->where('company_id', $tcsCompany->id)
            ->first();

        if ($existingCalendar) {
            DB::table('employees_calendar_attendances')->where('calendar_id', $existingCalendar->id)->delete();
            DB::table('employees_calendars')->where('id', $existingCalendar->id)->delete();
        }

        // Delete existing sample calendars
        DB::table('employees_calendar_attendances')->whereIn('calendar_id', [9, 10, 11, 12])->delete();
        DB::table('employees_calendars')->whereIn('id', [9, 10, 11, 12])->delete();

        // Create TCS Woodwork calendar
        $calendarId = DB::table('employees_calendars')->insertGetId([
            'name' => 'TCS Woodwork Shop Schedule',
            'timezone' => 'America/New_York',
            'hours_per_day' => 8,
            'full_time_required_hours' => 32, // 4 days x 8 hours
            'is_active' => true,
            'two_weeks_calendar' => false,
            'flexible_hours' => false,
            'company_id' => $tcsCompany->id,
            'creator_id' => $user?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create calendar attendances for Monday-Thursday (8am-5pm with 1hr lunch)
        $workDays = [
            ['name' => 'Monday Morning', 'day_of_week' => 'monday', 'day_period' => 'morning', 'hour_from' => '08:00:00', 'hour_to' => '12:00:00', 'duration_days' => 0.5, 'display_type' => 'working'],
            ['name' => 'Monday Lunch', 'day_of_week' => 'monday', 'day_period' => 'lunch', 'hour_from' => '12:00:00', 'hour_to' => '13:00:00', 'duration_days' => 0, 'display_type' => null],
            ['name' => 'Monday Afternoon', 'day_of_week' => 'monday', 'day_period' => 'afternoon', 'hour_from' => '13:00:00', 'hour_to' => '17:00:00', 'duration_days' => 0.5, 'display_type' => 'working'],
            ['name' => 'Tuesday Morning', 'day_of_week' => 'tuesday', 'day_period' => 'morning', 'hour_from' => '08:00:00', 'hour_to' => '12:00:00', 'duration_days' => 0.5, 'display_type' => 'working'],
            ['name' => 'Tuesday Lunch', 'day_of_week' => 'tuesday', 'day_period' => 'lunch', 'hour_from' => '12:00:00', 'hour_to' => '13:00:00', 'duration_days' => 0, 'display_type' => null],
            ['name' => 'Tuesday Afternoon', 'day_of_week' => 'tuesday', 'day_period' => 'afternoon', 'hour_from' => '13:00:00', 'hour_to' => '17:00:00', 'duration_days' => 0.5, 'display_type' => 'working'],
            ['name' => 'Wednesday Morning', 'day_of_week' => 'wednesday', 'day_period' => 'morning', 'hour_from' => '08:00:00', 'hour_to' => '12:00:00', 'duration_days' => 0.5, 'display_type' => 'working'],
            ['name' => 'Wednesday Lunch', 'day_of_week' => 'wednesday', 'day_period' => 'lunch', 'hour_from' => '12:00:00', 'hour_to' => '13:00:00', 'duration_days' => 0, 'display_type' => null],
            ['name' => 'Wednesday Afternoon', 'day_of_week' => 'wednesday', 'day_period' => 'afternoon', 'hour_from' => '13:00:00', 'hour_to' => '17:00:00', 'duration_days' => 0.5, 'display_type' => 'working'],
            ['name' => 'Thursday Morning', 'day_of_week' => 'thursday', 'day_period' => 'morning', 'hour_from' => '08:00:00', 'hour_to' => '12:00:00', 'duration_days' => 0.5, 'display_type' => 'working'],
            ['name' => 'Thursday Lunch', 'day_of_week' => 'thursday', 'day_period' => 'lunch', 'hour_from' => '12:00:00', 'hour_to' => '13:00:00', 'duration_days' => 0, 'display_type' => null],
            ['name' => 'Thursday Afternoon', 'day_of_week' => 'thursday', 'day_period' => 'afternoon', 'hour_from' => '13:00:00', 'hour_to' => '17:00:00', 'duration_days' => 0.5, 'display_type' => 'working'],
        ];

        $sort = 0;
        foreach ($workDays as $day) {
            DB::table('employees_calendar_attendances')->insert([
                'sort' => $sort++,
                'name' => $day['name'],
                'day_of_week' => $day['day_of_week'],
                'day_period' => $day['day_period'],
                'week_type' => null,
                'display_type' => $day['display_type'],
                'date_from' => null,
                'date_to' => null,
                'duration_days' => $day['duration_days'],
                'hour_from' => $day['hour_from'],
                'hour_to' => $day['hour_to'],
                'calendar_id' => $calendarId,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('TCS Woodwork shop schedule created successfully!');
        $this->command->info('Schedule: Monday-Thursday, 8am-5pm (1 hour lunch), America/New_York timezone');
    }
}
