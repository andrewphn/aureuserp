<?php

namespace Webkul\Sale\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Advanced Payment enumeration
 *
 */
enum AdvancedPayment: string implements HasLabel
{
    case DELIVERED = 'delivered';

    case PERCENTAGE = 'percentage';

    case FIXED = 'fixed';

    /**
     * Get the human-readable label for this payment type
     *
     * @return string Translated label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DELIVERED  => __('sales::enums/advanced-payment.delivered'),
            self::PERCENTAGE => __('sales::enums/advanced-payment.percentage'),
            self::FIXED      => __('sales::enums/advanced-payment.fixed'),
        };
    }

    /**
     * Get all payment types as key-value options for form selects
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::DELIVERED->value  => __('sales::enums/advanced-payment.delivered'),
            self::PERCENTAGE->value => __('sales::enums/advanced-payment.percentage'),
            self::FIXED->value      => __('sales::enums/advanced-payment.fixed'),
        ];
    }
}
