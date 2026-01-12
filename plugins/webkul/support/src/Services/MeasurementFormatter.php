<?php

namespace Webkul\Support\Services;

use Webkul\Support\Settings\MeasurementSettings;

/**
 * MeasurementFormatter Service
 *
 * Handles conversion and formatting of measurements based on global settings.
 * All measurements are stored in inches and converted for display only.
 */
class MeasurementFormatter
{
    protected ?MeasurementSettings $settings = null;

    /** Conversion factor: 1 inch = 25.4 mm */
    public const INCHES_TO_MM = 25.4;

    /**
     * Default settings if MeasurementSettings not yet configured.
     */
    protected array $defaults = [
        'display_unit' => 'imperial_decimal',
        'fraction_precision' => 8,
        'show_unit_symbol' => true,
        'metric_precision' => 0,
        'linear_feet_precision' => 2,
        'input_step' => 0.125,
    ];

    public function __construct(?MeasurementSettings $settings = null)
    {
        $this->settings = $settings;
    }

    /**
     * Get settings instance with fallback to defaults.
     */
    protected function getSettings(): MeasurementSettings
    {
        if ($this->settings === null) {
            try {
                $this->settings = app(MeasurementSettings::class);
            } catch (\Exception $e) {
                // Settings not yet migrated, return mock object with defaults
                return new class($this->defaults) {
                    public function __construct(private array $defaults) {}
                    public function __get($name) {
                        return $this->defaults[$name] ?? null;
                    }
                };
            }
        }

        return $this->settings;
    }

    /**
     * Format a dimension value from inches to the configured display format.
     *
     * @param float|null $inches Value in inches
     * @param bool|null $showSymbol Override symbol display (null = use settings)
     * @return string Formatted dimension string
     */
    public function format(?float $inches, ?bool $showSymbol = null): string
    {
        if ($inches === null) {
            return '-';
        }

        $settings = $this->getSettings();
        $showSymbol = $showSymbol ?? $settings->show_unit_symbol;

        return match ($settings->display_unit) {
            'imperial_fraction' => $this->formatFraction($inches, $showSymbol),
            'metric' => $this->formatMetric($inches, $showSymbol),
            default => $this->formatDecimal($inches, $showSymbol),
        };
    }

    /**
     * Format as imperial decimal (e.g., 24.5")
     */
    public function formatDecimal(float $inches, bool $showSymbol = true): string
    {
        // Format with 2 decimals, then trim trailing zeros
        $formatted = number_format($inches, 2);
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $showSymbol ? $formatted . '"' : $formatted;
    }

    /**
     * Format as imperial fraction (e.g., 24 1/2")
     */
    public function formatFraction(float $inches, bool $showSymbol = true): string
    {
        $settings = $this->getSettings();
        $precision = $settings->fraction_precision;

        $wholeNumber = (int) floor($inches);
        $decimal = $inches - $wholeNumber;

        // No fractional part
        if (abs($decimal) < 0.001) {
            return $showSymbol ? $wholeNumber . '"' : (string) $wholeNumber;
        }

        // Convert decimal to fraction of given precision
        $numerator = (int) round($decimal * $precision);

        // Rounded to 0
        if ($numerator === 0) {
            return $showSymbol ? $wholeNumber . '"' : (string) $wholeNumber;
        }

        // Rounded up to full unit
        if ($numerator === $precision) {
            $wholeNumber++;
            return $showSymbol ? $wholeNumber . '"' : (string) $wholeNumber;
        }

        // Reduce fraction to lowest terms
        $fraction = $this->reduceFraction($numerator, $precision);

        // Build result
        $result = $wholeNumber > 0
            ? "{$wholeNumber} {$fraction}"
            : $fraction;

        return $showSymbol ? $result . '"' : $result;
    }

    /**
     * Format as metric millimeters (e.g., 622 mm)
     */
    public function formatMetric(float $inches, bool $showSymbol = true): string
    {
        $settings = $this->getSettings();
        $mm = $inches * self::INCHES_TO_MM;
        $precision = $settings->metric_precision;

        $formatted = number_format($mm, $precision);

        return $showSymbol ? $formatted . ' mm' : $formatted;
    }

    /**
     * Format dimensions string (W x H or W x H x D)
     */
    public function formatDimensions(
        ?float $width,
        ?float $height,
        ?float $depth = null,
        string $separator = ' x '
    ): string {
        $parts = [
            $this->format($width, false) . '"W',
            $this->format($height, false) . '"H',
        ];

        if ($depth !== null) {
            $parts[] = $this->format($depth, false) . '"D';
        }

        return implode($separator, $parts);
    }

    /**
     * Format linear feet value
     */
    public function formatLinearFeet(float $linearFeet): string
    {
        $settings = $this->getSettings();
        $precision = $settings->linear_feet_precision;

        return number_format($linearFeet, $precision) . ' LF';
    }

    /**
     * Convert inches to millimeters
     */
    public function inchesToMm(float $inches): float
    {
        return $inches * self::INCHES_TO_MM;
    }

    /**
     * Convert millimeters to inches
     */
    public function mmToInches(float $mm): float
    {
        return $mm / self::INCHES_TO_MM;
    }

    /**
     * Get the current input step value
     */
    public function getInputStep(): float
    {
        return $this->getSettings()->input_step;
    }

    /**
     * Get the current display unit
     */
    public function getDisplayUnit(): string
    {
        return $this->getSettings()->display_unit;
    }

    /**
     * Get fraction precision
     */
    public function getFractionPrecision(): int
    {
        return $this->getSettings()->fraction_precision;
    }

    /**
     * Check if currently in fraction mode
     */
    public function isFractionMode(): bool
    {
        return $this->getSettings()->display_unit === 'imperial_fraction';
    }

    /**
     * Check if currently in metric mode
     */
    public function isMetricMode(): bool
    {
        return $this->getSettings()->display_unit === 'metric';
    }

    /**
     * Get all settings as array (for JavaScript)
     */
    public function getSettingsArray(): array
    {
        $settings = $this->getSettings();

        return [
            'displayUnit' => $settings->display_unit,
            'fractionPrecision' => $settings->fraction_precision,
            'showUnitSymbol' => $settings->show_unit_symbol,
            'metricPrecision' => $settings->metric_precision,
            'linearFeetPrecision' => $settings->linear_feet_precision,
            'inputStep' => $settings->input_step,
        ];
    }

    /**
     * Reduce a fraction to lowest terms
     */
    protected function reduceFraction(int $numerator, int $denominator): string
    {
        $gcd = $this->gcd($numerator, $denominator);
        $num = $numerator / $gcd;
        $den = $denominator / $gcd;

        return "{$num}/{$den}";
    }

    /**
     * Calculate greatest common divisor (Euclidean algorithm)
     */
    protected function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }
}
