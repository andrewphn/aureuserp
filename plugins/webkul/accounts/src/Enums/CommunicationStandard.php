<?php

namespace Webkul\Account\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Communication Standard enumeration
 *
 */
enum CommunicationStandard: string implements HasLabel
{
    case AUREUS = 'aureus';

    case EUROPEAN = 'european';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AUREUS   => __('accounts::enums/communication-standard.aureus'),
            self::EUROPEAN => __('accounts::enums/communication-standard.european'),
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
            self::AUREUS->value   => __('accounts::enums/communication-standard.aureus'),
            self::EUROPEAN->value => __('accounts::enums/communication-standard.european'),
        ];
    }
}
