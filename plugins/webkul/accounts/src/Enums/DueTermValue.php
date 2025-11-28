<?php

namespace Webkul\Account\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Due Term Value enumeration
 *
 */
enum DueTermValue: string implements HasLabel
{
    case PERCENT = 'percent';

    case FIXED = 'fixed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PERCENT => __('accounts::enums/due-term-value.percent'),
            self::FIXED   => __('accounts::enums/due-term-value.fixed'),
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
            self::PERCENT->value => __('accounts::enums/due-term-value.percent'),
            self::FIXED->value   => __('accounts::enums/due-term-value.fixed'),
        ];
    }
}
