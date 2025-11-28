<?php

namespace Webkul\Employee\Enums;

/**
 * Distance Unit enumeration
 *
 */
enum DistanceUnit: string
{
    case KILOMETER = 'kilometer';

    case METER = 'meter';

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::KILOMETER->value => __('employees::enums/distance-unit.kilometer'),
            self::METER->value     => __('employees::enums/distance-unit.meter'),
        ];
    }
}
