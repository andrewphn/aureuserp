<?php

namespace Webkul\Employee\Enums;

/**
 * Calendar Display Type enumeration
 *
 */
enum CalendarDisplayType: string
{
    case Working = 'working';

    case Off = 'off';

    case Holiday = 'holiday';

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::Working->value => __('employees::enums/calendar-display-type.working'),
            self::Off->value     => __('employees::enums/calendar-display-type.off'),
            self::Holiday->value => __('employees::enums/calendar-display-type.holiday'),
        ];
    }
}
