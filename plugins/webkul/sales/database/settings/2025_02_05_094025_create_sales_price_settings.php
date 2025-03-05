<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sales_price.enable_discount', true);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('sales_price.enable_discount');
    }
};
