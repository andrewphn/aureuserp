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
        $this->migrator->add('sales_price.enable_discount', true);
        $this->migrator->add('sales_price.enable_margin', true);
    }

    /**
     * Down
     *
     * @return void
     */
    public function down(): void
    {
        $this->migrator->deleteIfExists('sales_price.enable_discount');
        $this->migrator->deleteIfExists('sales_price.enable_margin');
    }
};
