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
        $this->migrator->add('inventories_product.enable_variants', true);
        $this->migrator->add('inventories_product.enable_uom', false);
        $this->migrator->add('inventories_product.enable_packagings', false);
    }

    /**
     * Down
     *
     * @return void
     */
    public function down(): void
    {
        $this->migrator->deleteIfExists('inventories_product.enable_variants');
        $this->migrator->deleteIfExists('inventories_product.enable_uom');
        $this->migrator->deleteIfExists('inventories_product.enable_packagings');
    }
};
