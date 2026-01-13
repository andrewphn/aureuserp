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

    /** Conversion factors */
    public const INCHES_TO_MM = 25.4;        // 1 inch = 25.4 mm
    public const INCHES_TO_CM = 2.54;        // 1 inch = 2.54 cm
    public const INCHES_TO_M = 0.0254;        // 1 inch = 0.0254 m
    public const INCHES_TO_FEET = 1 / 12;    // 1 inch = 1/12 feet
    public const FEET_TO_INCHES = 12;        // 1 foot = 12 inches
    public const INCHES_TO_YARDS = 1 / 36;   // 1 inch = 1/36 yards
    public const YARDS_TO_INCHES = 36;       // 1 yard = 36 inches
    public const SQ_INCHES_TO_SQ_FEET = 1 / 144; // 1 sq inch = 1/144 sq feet
    public const SQ_FEET_TO_SQ_INCHES = 144;     // 1 sq foot = 144 sq inches
    public const CUBIC_INCHES_TO_CUBIC_FEET = 1 / 1728; // 1 cu inch = 1/1728 cu feet
    public const CUBIC_FEET_TO_CUBIC_INCHES = 1728;     // 1 cu foot = 1728 cu inches

    /**
     * Default settings if MeasurementSettings not yet configured.
     * Default display_unit is 'imperial_fraction' to show fractional inches by default.
     */
    protected array $defaults = [
        'display_unit' => 'imperial_fraction',
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
     * Public method to allow access from other classes.
     */
    public function getSettings(): MeasurementSettings
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
        // Format with 4 decimals to preserve precision (e.g., 41.3125), then trim trailing zeros
        // This ensures fractional measurements like "41-5/16" (41.3125) display correctly
        $formatted = number_format($inches, 4);
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
     * Convert inches to feet
     */
    public function inchesToFeet(float $inches): float
    {
        return $inches * self::INCHES_TO_FEET;
    }

    /**
     * Convert feet to inches
     */
    public function feetToInches(float $feet): float
    {
        return $feet * self::FEET_TO_INCHES;
    }

    /**
     * Convert inches to centimeters
     */
    public function inchesToCm(float $inches): float
    {
        return $inches * self::INCHES_TO_CM;
    }

    /**
     * Convert centimeters to inches
     */
    public function cmToInches(float $cm): float
    {
        return $cm / self::INCHES_TO_CM;
    }

    /**
     * Convert inches to meters
     */
    public function inchesToMeters(float $inches): float
    {
        return $inches * self::INCHES_TO_M;
    }

    /**
     * Convert meters to inches
     */
    public function metersToInches(float $meters): float
    {
        return $meters / self::INCHES_TO_M;
    }

    /**
     * Convert inches to yards
     */
    public function inchesToYards(float $inches): float
    {
        return $inches * self::INCHES_TO_YARDS;
    }

    /**
     * Convert yards to inches
     */
    public function yardsToInches(float $yards): float
    {
        return $yards * self::YARDS_TO_INCHES;
    }

    /**
     * Convert square inches to square feet
     */
    public function sqInchesToSqFeet(float $sqInches): float
    {
        return $sqInches * self::SQ_INCHES_TO_SQ_FEET;
    }

    /**
     * Convert square feet to square inches
     */
    public function sqFeetToSqInches(float $sqFeet): float
    {
        return $sqFeet * self::SQ_FEET_TO_SQ_INCHES;
    }

    /**
     * Calculate square feet from width and height (in inches)
     */
    public function calculateSquareFeet(float $widthInches, float $heightInches): float
    {
        $sqInches = $widthInches * $heightInches;
        return $this->sqInchesToSqFeet($sqInches);
    }

    /**
     * Convert cubic inches to cubic feet
     */
    public function cubicInchesToCubicFeet(float $cubicInches): float
    {
        return $cubicInches * self::CUBIC_INCHES_TO_CUBIC_FEET;
    }

    /**
     * Convert cubic feet to cubic inches
     */
    public function cubicFeetToCubicInches(float $cubicFeet): float
    {
        return $cubicFeet * self::CUBIC_FEET_TO_CUBIC_INCHES;
    }

    /**
     * Calculate cubic feet from width, height, and depth (in inches)
     */
    public function calculateCubicFeet(float $widthInches, float $heightInches, float $depthInches): float
    {
        $cubicInches = $widthInches * $heightInches * $depthInches;
        return $this->cubicInchesToCubicFeet($cubicInches);
    }

    /**
     * Calculate linear feet from inches
     */
    public function calculateLinearFeet(float $inches): float
    {
        return $this->inchesToFeet($inches);
    }

    /**
     * Format square feet value
     */
    public function formatSquareFeet(float $sqFeet, int $precision = 2): string
    {
        return number_format($sqFeet, $precision) . ' sq ft';
    }

    /**
     * Format cubic feet value
     */
    public function formatCubicFeet(float $cubicFeet, int $precision = 2): string
    {
        return number_format($cubicFeet, $precision) . ' cu ft';
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

    /**
     * Parse measurement input with automatic unit detection and convert to decimal inches
     * 
     * Supports woodworker-friendly formats with automatic unit detection:
     * - "12.5" -> 12.5 (decimal inches)
     * - "12.5 in" or "12.5\"" -> 12.5 (inches)
     * - "12.5 ft" or "12.5'" -> 150 (feet to inches)
     * - "12.5 yd" -> 450 (yards to inches)
     * - "12.5 mm" -> 0.492 (millimeters to inches)
     * - "12.5 cm" -> 4.921 (centimeters to inches)
     * - "12.5 m" -> 492.125 (meters to inches)
     * - "12 1/2" -> 12.5 (whole + fraction with space, inches)
     * - "12-1/2" -> 12.5 (whole + fraction with dash, inches)
     * - "41 5/16" -> 41.3125 (whole + fraction, inches)
     * - "41-5/16" -> 41.3125 (whole + fraction with dash, inches)
     * - "3/4" -> 0.75 (fraction only, inches)
     * - "1/2" -> 0.5 (fraction only, inches)
     * - "2'" -> 24 (feet to inches)
     * - "2' 6" -> 30 (feet and inches)
     * - "2' 6 1/2" -> 30.5 (feet, inches, and fraction)
     * 
     * @param string|float|null $input The input measurement
     * @return float|null The decimal value in inches, or null if invalid
     */
    public function parseFractionalMeasurement($input): ?float
    {
        if ($input === null || $input === '') {
            return null;
        }

        // If already a number, return it
        if (is_numeric($input)) {
            return (float) $input;
        }

        // Convert to string for parsing
        $input = trim((string) $input);

        // Detect and extract unit suffix (yd, ft, in, ", mm, cm, m)
        $unit = null;
        $unitPattern = '/\s*(yd|yard|yards|ft|feet|foot|\'|in|inch|inches|"|mm|millimeter|millimeters|cm|centimeter|centimeters|m|meter|meters)\s*$/i';
        if (preg_match($unitPattern, $input, $unitMatches)) {
            $unitStr = strtolower(trim($unitMatches[1]));
            $input = preg_replace($unitPattern, '', $input);
            
            // Normalize unit strings
            $unit = match($unitStr) {
                'yd', 'yard', 'yards' => 'yards',
                'ft', 'feet', 'foot', "'" => 'feet',
                'in', 'inch', 'inches', '"' => 'inches',
                'mm', 'millimeter', 'millimeters' => 'millimeters',
                'cm', 'centimeter', 'centimeters' => 'centimeters',
                'm', 'meter', 'meters' => 'meters',
                default => 'inches',
            };
        }

        // Handle feet notation (e.g., "2'" or "2' 6" or "2' 6 1/2")
        // This takes precedence if unit wasn't detected from suffix
        if ($unit === null && preg_match("/^(\d+)'(?:\s*(\d+(?:\s+\d+\/\d+|\-\d+\/\d+|\/\d+)?)?)?$/", $input, $feetMatches)) {
            $feet = (int) $feetMatches[1];
            $inches = 0.0;

            if (!empty($feetMatches[2])) {
                // Parse the inches part (which may include fractions)
                $inches = $this->parseFractionalMeasurement($feetMatches[2]) ?? 0;
            }

            return ($feet * 12) + $inches;
        }

        // Pattern: Match "12 1/2" or "41 5/16" (whole number space fraction)
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $input, $matches)) {
            $whole = (int) $matches[1];
            $numerator = (int) $matches[2];
            $denominator = (int) $matches[3];

            if ($denominator == 0) {
                return null;
            }

            $decimal = $whole + ($numerator / $denominator);
            
            // Convert to inches based on detected unit
            if ($unit !== null && $unit !== 'inches') {
                return match($unit) {
                    'feet' => $this->feetToInches($decimal),
                    'yards' => $this->yardsToInches($decimal),
                    'millimeters' => $this->mmToInches($decimal),
                    'centimeters' => $this->cmToInches($decimal),
                    'meters' => $this->metersToInches($decimal),
                    default => $decimal,
                };
            }
            
            return $decimal;
        }

        // Format: "12-1/2" or "41-5/16" (whole number dash fraction)
        if (preg_match('/^(\d+)-(\d+)\/(\d+)$/', $input, $matches)) {
            $whole = (int) $matches[1];
            $numerator = (int) $matches[2];
            $denominator = (int) $matches[3];

            if ($denominator == 0) {
                return null;
            }

            $decimal = $whole + ($numerator / $denominator);
            
            // Convert to inches based on detected unit
            if ($unit !== null && $unit !== 'inches') {
                return match($unit) {
                    'feet' => $this->feetToInches($decimal),
                    'yards' => $this->yardsToInches($decimal),
                    'millimeters' => $this->mmToInches($decimal),
                    'centimeters' => $this->cmToInches($decimal),
                    'meters' => $this->metersToInches($decimal),
                    default => $decimal,
                };
            }
            
            return $decimal;
        }

        // Format: "3/4" (just fraction)
        if (preg_match('/^(\d+)\/(\d+)$/', $input, $matches)) {
            $numerator = (int) $matches[1];
            $denominator = (int) $matches[2];

            if ($denominator == 0) {
                return null;
            }

            $decimal = $numerator / $denominator;
            
            // Convert to inches based on detected unit
            if ($unit !== null && $unit !== 'inches') {
                return match($unit) {
                    'feet' => $this->feetToInches($decimal),
                    'yards' => $this->yardsToInches($decimal),
                    'millimeters' => $this->mmToInches($decimal),
                    'centimeters' => $this->cmToInches($decimal),
                    'meters' => $this->metersToInches($decimal),
                    default => $decimal,
                };
            }
            
            return $decimal;
        }

        // Try to parse as decimal
        if (is_numeric($input)) {
            $decimal = (float) $input;
            
            // Convert to inches based on detected unit
            if ($unit !== null && $unit !== 'inches') {
                return match($unit) {
                    'feet' => $this->feetToInches($decimal),
                    'yards' => $this->yardsToInches($decimal),
                    'millimeters' => $this->mmToInches($decimal),
                    'centimeters' => $this->cmToInches($decimal),
                    'meters' => $this->metersToInches($decimal),
                    default => $decimal,
                };
            }
            
            return $decimal;
        }

        return null;
    }

    /**
     * Static helper for parsing fractional measurements
     * Convenience method that creates a new instance and calls parseFractionalMeasurement
     * 
     * @param string|float|null $input The input measurement
     * @return float|null The decimal value or null if invalid
     */
    public static function parse($input): ?float
    {
        $formatter = new self();
        return $formatter->parseFractionalMeasurement($input);
    }
}
