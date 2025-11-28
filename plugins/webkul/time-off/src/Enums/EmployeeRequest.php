<?php

namespace Webkul\TimeOff\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Employee Request form request
 *
 */
enum EmployeeRequest: string implements HasLabel
{
    case YES = 'yes';

    case NO = 'no';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::YES => __('time-off::enums/employee-request.yes'),
            self::NO  => __('time-off::enums/employee-request.no'),
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
            self::YES->value => __('time-off::enums/employee-request.yes'),
            self::NO->value  => __('time-off::enums/employee-request.no'),
        ];
    }
}
