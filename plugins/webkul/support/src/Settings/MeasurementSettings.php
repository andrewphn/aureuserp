<?php

namespace Webkul\Support\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Global Measurement Settings
 *
 * System-wide configuration for how measurements are displayed.
 * All measurements are stored in inches and converted for display only.
 */
class MeasurementSettings extends Settings
{
    /**
     * Measurement display unit preference.
     * Options: 'imperial_decimal', 'imperial_fraction', 'metric'
     */
    public string $display_unit;

    /**
     * Fraction precision for imperial_fraction mode.
     * Options: 2 (1/2), 4 (1/4), 8 (1/8), 16 (1/16)
     */
    public int $fraction_precision;

    /**
     * Whether to show inch/mm symbol suffix.
     */
    public bool $show_unit_symbol;

    /**
     * Metric display precision (decimal places for mm).
     */
    public int $metric_precision;

    /**
     * Linear feet display precision (decimal places).
     */
    public int $linear_feet_precision;

    /**
     * Input step increment for form fields (in inches).
     * Default: 0.125 (1/8")
     */
    public float $input_step;

    /**
     * Settings group name.
     */
    public static function group(): string
    {
        return 'measurement';
    }
}
