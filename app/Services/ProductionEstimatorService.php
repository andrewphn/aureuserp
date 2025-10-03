<?php

namespace App\Services;

use Webkul\Support\Models\Company;

/**
 * Production Estimator Service
 *
 * Calculates estimated production time based on linear feet and company capacity.
 * Used for project planning and resource allocation.
 */
class ProductionEstimatorService
{
    /**
     * Calculate production estimate for given linear feet
     *
     * @param float $linearFeet Total linear feet to produce
     * @param int|Company $company Company ID or Company model
     * @return array{
     *   hours: float,
     *   days: float,
     *   weeks: float,
     *   months: float,
     *   working_days_per_week: int,
     *   working_days_per_month: int,
     *   formatted: string,
     *   details: array
     * }|null
     */
    public static function calculate(float $linearFeet, int|Company $company): ?array
    {
        // Get company model if ID was passed
        if (is_int($company)) {
            $company = Company::find($company);
        }

        // Validate company and capacity data
        if (!$company || !$company->shop_capacity_per_day || !$company->shop_capacity_per_hour) {
            return null;
        }

        // Get working schedule data (defaults if not set)
        $workingHoursPerDay = $company->working_hours_per_day ?? 8.0;
        $workingDaysPerMonth = $company->working_days_per_month ?? 17;

        // TCS works 4 days/week (Mon-Thu)
        $workingDaysPerWeek = 4;

        // Calculate production time
        $hoursNeeded = $linearFeet / $company->shop_capacity_per_hour;
        $daysNeeded = $linearFeet / $company->shop_capacity_per_day;

        // Calculate actual calendar weeks (production days / working days per week)
        // Then convert to calendar weeks by assuming 7 days per week
        $productionWeeks = $daysNeeded / $workingDaysPerWeek;
        $calendarWeeks = $productionWeeks; // Since we work 4 out of 7 days, production weeks ≈ calendar weeks

        $monthsNeeded = $daysNeeded / $workingDaysPerMonth;

        return [
            // Raw numbers
            'hours' => round($hoursNeeded, 2),
            'days' => round($daysNeeded, 2),
            'weeks' => round($calendarWeeks, 2),
            'months' => round($monthsNeeded, 2),

            // Working schedule info
            'working_days_per_week' => $workingDaysPerWeek,
            'working_days_per_month' => $workingDaysPerMonth,
            'working_hours_per_day' => $workingHoursPerDay,

            // Company capacity
            'shop_capacity_per_day' => $company->shop_capacity_per_day,
            'shop_capacity_per_hour' => $company->shop_capacity_per_hour,

            // Formatted output
            'formatted' => static::formatEstimate($hoursNeeded, $daysNeeded, $calendarWeeks, $monthsNeeded),

            // Detailed breakdown
            'details' => [
                'linear_feet' => $linearFeet,
                'company_name' => $company->name,
                'company_acronym' => $company->acronym,
                'capacity_per_day' => "{$company->shop_capacity_per_day} LF/day",
                'capacity_per_hour' => "{$company->shop_capacity_per_hour} LF/hour",
            ],
        ];
    }

    /**
     * Format the estimate into a human-readable string
     */
    protected static function formatEstimate(float $hours, float $days, float $weeks, float $months): string
    {
        $parts = [];

        // Determine which units to show based on magnitude
        if ($months >= 1) {
            $parts[] = static::pluralize($months, 'month');
        }

        if ($weeks >= 1 && $months < 3) {
            $parts[] = static::pluralize($weeks, 'week');
        }

        if ($days >= 1 && $weeks < 4) {
            $parts[] = static::pluralize($days, 'day');
        }

        if ($hours >= 1 && $days < 5) {
            $parts[] = static::pluralize($hours, 'hour');
        }

        return implode(', ', $parts) ?: 'Less than 1 hour';
    }

    /**
     * Helper to pluralize units
     */
    protected static function pluralize(float $value, string $unit): string
    {
        $rounded = round($value, 1);
        $plural = $rounded == 1 ? $unit : $unit . 's';
        return "{$rounded} {$plural}";
    }

    /**
     * Get a short summary format (for UI display)
     */
    public static function getShortSummary(float $linearFeet, int|Company $company): ?string
    {
        $estimate = static::calculate($linearFeet, $company);

        if (!$estimate) {
            return null;
        }

        // Show the most relevant time unit
        if ($estimate['months'] >= 1) {
            return static::pluralize($estimate['months'], 'month') .
                   " ({$estimate['days']} production days)";
        } elseif ($estimate['weeks'] >= 1) {
            return static::pluralize($estimate['weeks'], 'week') .
                   " ({$estimate['days']} days)";
        } else {
            return static::pluralize($estimate['days'], 'day') .
                   " ({$estimate['hours']} hours)";
        }
    }

    /**
     * Get detailed breakdown (for tooltips or detailed views)
     */
    public static function getDetailedBreakdown(float $linearFeet, int|Company $company): ?string
    {
        $estimate = static::calculate($linearFeet, $company);

        if (!$estimate) {
            return null;
        }

        return sprintf(
            "%s LF @ %s LF/day (%s LF/hour)\n" .
            "≈ %s hours\n" .
            "≈ %s production days\n" .
            "≈ %s calendar weeks (%d working days/week)\n" .
            "≈ %s months (%d working days/month)",
            number_format($linearFeet, 2),
            $estimate['shop_capacity_per_day'],
            $estimate['shop_capacity_per_hour'],
            number_format($estimate['hours'], 1),
            number_format($estimate['days'], 1),
            number_format($estimate['weeks'], 1),
            $estimate['working_days_per_week'],
            number_format($estimate['months'], 1),
            $estimate['working_days_per_month']
        );
    }
}
