<?php

namespace Webkul\Project\Enums;

/**
 * Budget Range enumeration for BI tracking
 */
enum BudgetRange: string
{
    case RANGE_5K_15K = '5k_15k';
    case RANGE_15K_30K = '15k_30k';
    case RANGE_30K_50K = '30k_50k';
    case RANGE_50K_100K = '50k_100k';
    case RANGE_100K_PLUS = '100k_plus';

    /**
     * Options for select fields
     */
    public static function options(): array
    {
        return [
            self::RANGE_5K_15K->value => '$5K - $15K',
            self::RANGE_15K_30K->value => '$15K - $30K',
            self::RANGE_30K_50K->value => '$30K - $50K',
            self::RANGE_50K_100K->value => '$50K - $100K',
            self::RANGE_100K_PLUS->value => '$100K+',
        ];
    }

    /**
     * Get display label for a value
     */
    public static function label(string $value): string
    {
        return self::options()[$value] ?? $value;
    }

    /**
     * Get midpoint value for calculations (in dollars)
     */
    public static function midpoint(string $value): int
    {
        return match($value) {
            self::RANGE_5K_15K->value => 10000,
            self::RANGE_15K_30K->value => 22500,
            self::RANGE_30K_50K->value => 40000,
            self::RANGE_50K_100K->value => 75000,
            self::RANGE_100K_PLUS->value => 150000,
            default => 0,
        };
    }
}
