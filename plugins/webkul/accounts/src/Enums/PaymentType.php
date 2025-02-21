<?php

namespace Webkul\Account\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentType: string implements HasLabel
{
    case SEND = 'outbound';

    case RECEIVE = 'inbound';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SEND => __('accounts::enums/payment-type.send'),
            self::RECEIVE => __('accounts::enums/payment-type.receive'),
        };
    }

    public static function options(): array
    {
        return [
            self::SEND->value => __('accounts::enums/payment-type.send'),
            self::RECEIVE->value => __('accounts::enums/payment-type.receive'),
        ];
    }
}
