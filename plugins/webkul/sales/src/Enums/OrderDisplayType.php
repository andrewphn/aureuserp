<?php

namespace Webkul\Sale\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Order Display Type enumeration
 *
 */
enum OrderDisplayType: string implements HasLabel
{
    case SECTION = 'section';

    case NOTE = 'note';

    /**
     * Get the label for this display type
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SECTION => __('sales::enums/order-display-type.section'),
            self::NOTE    => __('sales::enums/order-display-type.note'),
        };
    }

    /**
     * Options
     *
     * @return array
     */
    public function options(): array
    {
        return [
            self::SECTION->value => __('sales::enums/order-display-type.section'),
            self::NOTE->value    => __('sales::enums/order-display-type.note'),
        ];
    }
}
