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
        $this->migrator->add('general.enable_user_invitation', true);
        $this->migrator->add('general.enable_reset_password', true);
        $this->migrator->add('general.default_role_id', null);
        $this->migrator->add('general.default_company_id', null);
    }
};
