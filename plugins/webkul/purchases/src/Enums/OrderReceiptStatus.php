<?php

namespace Webkul\Purchase\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderReceiptStatus: string implements HasColor, HasLabel
{
    case NO = 'no';

    case TO_RECEIVED = 'to_received';

    case RECEIVED = 'received';

    public static function options(): array
    {
        return [
            self::NO->value          => __('purchases::enums/order-receipt-status.no'),
            self::TO_RECEIVED->value => __('purchases::enums/order-receipt-status.to-received'),
            self::RECEIVED->value    => __('purchases::enums/order-receipt-status.received'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::NO          => __('purchases::enums/order-receipt-status.no'),
            self::TO_RECEIVED => __('purchases::enums/order-receipt-status.to-received'),
            self::RECEIVED    => __('purchases::enums/order-receipt-status.received'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NO          => 'gray',
            self::TO_RECEIVED => 'warning',
            self::RECEIVED    => 'success',
        };
    }
}
