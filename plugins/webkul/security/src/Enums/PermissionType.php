<?php

namespace Webkul\Security\Enums;

/**
 * Permission Type enumeration
 *
 */
enum PermissionType: string
{
    case GROUP = 'group';

    case INDIVIDUAL = 'individual';

    case GLOBAL = 'global';

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::GROUP->value      => __('security::enums/permission-type.group'),
            self::INDIVIDUAL->value => __('security::enums/permission-type.individual'),
            self::GLOBAL->value     => __('security::enums/permission-type.global'),
        ];
    }
}
