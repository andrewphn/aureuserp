<?php

namespace Webkul\TimeOff\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Time Type enumeration
 *
 */
enum TimeType: string implements HasLabel
{
    case LEAVE = 'leave';

    case OTHER = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LEAVE => __('time-off::enums/time-type.leave'),
            self::OTHER => __('time-off::enums/time-type.other'),
        };
    }

    /**
     * Options
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::LEAVE->value => __('time-off::enums/time-type.leave'),
            self::OTHER->value => __('time-off::enums/time-type.other'),
        ];
    }
}
