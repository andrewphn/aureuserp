<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new /**
 * extends class
 *
 */
class extends SettingsMigration
{
    /**
     * Up
     *
     * @return void
     */
    public function up(): void
    {
        $this->migrator->add('time.enable_timesheets', false);
    }

    /**
     * Down
     *
     * @return void
     */
    public function down(): void
    {
        $this->migrator->delete('time.enable_timesheets');
    }
};
