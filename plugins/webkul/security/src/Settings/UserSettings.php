<?php

namespace Webkul\Security\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * User Settings class
 *
 */
class UserSettings extends Settings
{
    public bool $enable_user_invitation;

    public bool $enable_reset_password;

    public ?int $default_role_id;

    public ?int $default_company_id;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'general';
    }
}
