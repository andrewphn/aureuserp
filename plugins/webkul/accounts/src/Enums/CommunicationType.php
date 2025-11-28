<?php

namespace Webkul\Account\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Communication Type enumeration
 *
 */
enum CommunicationType: string implements HasLabel
{
    case NONE = 'open';

    case PARTNER = 'partner';

    case INVOICE = 'invoice';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NONE    => __('accounts::enums/communication-type.open'),
            self::PARTNER => __('accounts::enums/communication-type.partner'),
            self::INVOICE => __('accounts::enums/communication-type.invoice'),
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
            self::NONE->value    => __('accounts::enums/communication-type.open'),
            self::PARTNER->value => __('accounts::enums/communication-type.partner'),
            self::INVOICE->value => __('accounts::enums/communication-type.invoice'),
        ];
    }
}
