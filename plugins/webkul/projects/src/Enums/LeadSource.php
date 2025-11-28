<?php

namespace Webkul\Project\Enums;

/**
 * Lead Source enumeration for BI tracking
 */
enum LeadSource: string
{
    case TROTT_NANTUCKET = 'trott_nantucket';
    case REFERRAL = 'referral';
    case WALK_IN = 'walk_in';
    case WEBSITE = 'website';
    case REPEAT_CUSTOMER = 'repeat_customer';
    case OTHER = 'other';

    /**
     * Options for select fields
     */
    public static function options(): array
    {
        return [
            self::TROTT_NANTUCKET->value => 'Trott/Nantucket Partners',
            self::REFERRAL->value => 'Referral',
            self::WALK_IN->value => 'Walk-in',
            self::WEBSITE->value => 'Website',
            self::REPEAT_CUSTOMER->value => 'Repeat Customer',
            self::OTHER->value => 'Other',
        ];
    }

    /**
     * Get display label for a value
     */
    public static function label(string $value): string
    {
        return self::options()[$value] ?? $value;
    }
}
