<?php

namespace Webkul\Security\Enums;

/**
 * Company Status enumeration
 *
 */
enum CompanyStatus: string
{
    case ACTIVE = 'active';

    case INACTIVE = 'inactive';

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::ACTIVE->value      => __('security::enums/company-status.active'),
            self::INACTIVE->value    => __('security::enums/company-status.inactive'),
        ];
    }
}
