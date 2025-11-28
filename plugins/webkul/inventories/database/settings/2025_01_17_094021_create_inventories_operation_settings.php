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
        $this->migrator->add('inventories_operation.enable_packages', false);
        $this->migrator->add('inventories_operation.enable_warnings', false);
        $this->migrator->add('inventories_operation.enable_reception_report', false);
        $this->migrator->add('inventories_operation.annual_inventory_day', 31);
        $this->migrator->add('inventories_operation.annual_inventory_month', 12);
    }

    /**
     * Down
     *
     * @return void
     */
    public function down(): void
    {
        $this->migrator->deleteIfExists('inventories_operation.enable_packages');
        $this->migrator->deleteIfExists('inventories_operation.enable_warnings');
        $this->migrator->deleteIfExists('inventories_operation.enable_reception_report');
        $this->migrator->deleteIfExists('inventories_operation.annual_inventory_day');
        $this->migrator->deleteIfExists('inventories_operation.annual_inventory_month');
    }
};
