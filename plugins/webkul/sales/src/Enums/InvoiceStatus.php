<?php

namespace Webkul\Sale\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Invoice Status enumeration
 *
 */
enum InvoiceStatus: string implements HasLabel
{
    case UP_SELLING = 'up_selling';

    case INVOICED = 'invoiced';

    case TO_INVOICE = 'to_invoice';

    case NO = 'no';

    /**
     * Get the human-readable label for this invoice status
     *
     * @return string Translated label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::UP_SELLING   => __('sales::enums/invoice-status.up-selling'),
            self::INVOICED     => __('sales::enums/invoice-status.invoiced'),
            self::TO_INVOICE   => __('sales::enums/invoice-status.to-invoice'),
            self::NO           => __('sales::enums/invoice-status.no'),
        };
    }

    /**
     * Get all invoice statuses as key-value options for form selects
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::UP_SELLING->value   => __('sales::enums/invoice-status.up-selling'),
            self::INVOICED->value     => __('sales::enums/invoice-status.invoiced'),
            self::TO_INVOICE->value   => __('sales::enums/invoice-status.to-invoice'),
            self::NO->value           => __('sales::enums/invoice-status.no'),
        ];
    }
}
