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
        $this->migrator->add('inventories_logistic.enable_dropshipping', false);
    }

    /**
     * Down
     *
     * @return void
     */
    public function down(): void
    {
        $this->migrator->deleteIfExists('inventories_logistic.enable_dropshipping');
    }
};
