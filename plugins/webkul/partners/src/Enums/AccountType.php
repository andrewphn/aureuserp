<?php

namespace Webkul\Partner\Enums;

use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasLabel
{
    case INDIVIDUAL = 'individual';

    case COMPANY = 'company';

    public static function options(): array
    {
        return [
            self::INDIVIDUAL->value => __('partners::enums/account-type.individual'),
            self::COMPANY->value    => __('partners::enums/account-type.company'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::INDIVIDUAL => __('partners::enums/account-type.individual'),
            self::COMPANY    => __('partners::enums/account-type.company'),
        };
    }
}
