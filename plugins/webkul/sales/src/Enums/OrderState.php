<?php

namespace Webkul\Sale\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Order State enumeration
 *
 */
enum OrderState: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';

    case SENT = 'sent';

    case SALE = 'sale';

    case CANCEL = 'cancel';

    /**
     * Get the human-readable label for this order state
     *
     * @return string Translated label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT   => __('sales::enums/order-state.draft'),
            self::SENT    => __('sales::enums/order-state.sent'),
            self::SALE    => __('sales::enums/order-state.sale'),
            self::CANCEL  => __('sales::enums/order-state.cancel'),
        };
    }

    /**
     * Get all order states as key-value options for form selects
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::DRAFT->value   => __('sales::enums/order-state.draft'),
            self::SENT->value    => __('sales::enums/order-state.sent'),
            self::SALE->value    => __('sales::enums/order-state.sale'),
            self::CANCEL->value  => __('sales::enums/order-state.cancel'),
        ];
    }

    /**
     * Get the color associated with this order state for UI display
     *
     * @return string|null Filament color name
     */
    public function getColor(): ?string
    {
        return match ($this) {
            self::DRAFT  => 'gray',
            self::SENT   => 'primary',
            self::SALE   => 'success',
            self::CANCEL => 'danger',
        };
    }
}
