<?php

namespace Webkul\TimeOff\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Accrued Gain Time enumeration
 *
 */
enum AccruedGainTime: string implements HasLabel
{
    case START = 'start';

    case END = 'end';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::START => __('time-off::enums/accrued-gain-time.start'),
            self::END   => __('time-off::enums/accrued-gain-time.end'),
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
            self::START->value => __('time-off::enums/accrued-gain-time.start'),
            self::END->value   => __('time-off::enums/accrued-gain-time.end'),
        ];
    }
}
