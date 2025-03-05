<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sales_product.enable_variants', true);
        $this->migrator->add('sales_product.enable_uom', false);
        $this->migrator->add('sales_product.enable_packagings', false);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('sales_product.enable_variants');
        $this->migrator->deleteIfExists('sales_product.enable_uom');
        $this->migrator->deleteIfExists('sales_product.enable_packagings');
    }
};
