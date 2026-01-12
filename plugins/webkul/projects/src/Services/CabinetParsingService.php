<?php

namespace Webkul\Project\Services;

/**
 * Cabinet Parsing Service
 * 
 * Handles parsing logic for cabinet codes and dimension inputs
 */
class CabinetParsingService
{
    /**
     * Parse cabinet name to extract type and dimensions
     * Examples: B24 → Base 24", W3012 → Wall 30"x12", SB36 → Sink Base 36"
     * 
     * @param string $name Cabinet code/name
     * @return array{type: string|null, width: float|null}
     */
    public static function parseFromName(string $name): array
    {
        $name = strtoupper(trim($name));
        $result = ['type' => null, 'width' => null];

        $patterns = [
            // B24, DB24, SB36 - Base cabinets
            '/^(S?B|DB|BBC|LS|LZ)(\d+)$/' => ['type' => 'base', 'width_index' => 2],

            // W2430, W3012 - Wall cabinets (width x height)
            '/^(W|U)(\d{2})(\d{2})$/' => ['type' => 'wall', 'width_index' => 2],

            // W24, U30 - Simple wall
            '/^(W|U)(\d+)$/' => ['type' => 'wall', 'width_index' => 2],

            // T24, P24 - Tall/Pantry
            '/^(T|TP|P)(\d+)$/' => ['type' => 'tall', 'width_index' => 2],

            // V24, VD24 - Vanity
            '/^V(D)?(\d+)$/' => ['type' => 'vanity', 'width_index' => 2],
        ];

        foreach ($patterns as $pattern => $config) {
            if (preg_match($pattern, $name, $matches)) {
                $result['type'] = $config['type'];
                $result['width'] = (float) $matches[$config['width_index']];
                break;
            }
        }

        return $result;
    }

    /**
     * Parse various width input formats
     * Handles: "24", "24in", "2ft", "2'", etc.
     * 
     * @param string $input Width input string
     * @return float|null Parsed width in inches, or null if invalid
     */
    public static function parseWidthInput(string $input): ?float
    {
        $input = trim(strtolower($input));

        // Remove common suffixes
        $input = preg_replace('/\s*(in|inch|inches|"|\'\'|ft|feet|foot)\s*$/', '', $input);

        // Handle feet input (2ft → 24)
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(?:ft|feet|foot|\')?$/', $input, $matches)) {
            return (float) $matches[1] * 12;
        }

        // Handle inches
        if (is_numeric($input)) {
            return (float) $input;
        }

        return null;
    }

    /**
     * Parse fractional measurement input and convert to decimal inches
     * 
     * Supports woodworker-friendly formats:
     * - "12.5" -> 12.5 (decimal)
     * - "12 1/2" -> 12.5 (whole + fraction with space)
     * - "12-1/2" -> 12.5 (whole + fraction with dash)
     * - "41 5/16" -> 41.3125 (whole + fraction)
     * - "41-5/16" -> 41.3125 (whole + fraction with dash)
     * - "3/4" -> 0.75 (fraction only)
     * - "1/2" -> 0.5
     * - "2'" -> 24 (feet to inches)
     * - "2' 6" -> 30 (feet and inches)
     * - "2' 6 1/2" -> 30.5 (feet, inches, and fraction)
     * 
     * @param string|float|null $input The input measurement
     * @return float|null The decimal value or null if invalid
     */
    public static function parseFractionalMeasurement($input): ?float
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

        // Handle feet notation first (e.g., "2'" or "2' 6" or "2' 6 1/2")
        if (preg_match("/^(\d+)'(?:\s*(\d+(?:\s+\d+\/\d+|\-\d+\/\d+|\/\d+)?)?)?$/", $input, $feetMatches)) {
            $feet = (int) $feetMatches[1];
            $inches = 0.0;

            if (!empty($feetMatches[2])) {
                // Parse the inches part (which may include fractions)
                $inches = static::parseFractionalMeasurement($feetMatches[2]) ?? 0;
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

            return $whole + ($numerator / $denominator);
        }

        // Format: "12-1/2" or "41-5/16" (whole number dash fraction)
        if (preg_match('/^(\d+)-(\d+)\/(\d+)$/', $input, $matches)) {
            $whole = (int) $matches[1];
            $numerator = (int) $matches[2];
            $denominator = (int) $matches[3];

            if ($denominator == 0) {
                return null;
            }

            return $whole + ($numerator / $denominator);
        }

        // Format: "3/4" (just fraction)
        if (preg_match('/^(\d+)\/(\d+)$/', $input, $matches)) {
            $numerator = (int) $matches[1];
            $denominator = (int) $matches[2];

            if ($denominator == 0) {
                return null;
            }

            return $numerator / $denominator;
        }

        // Try to parse as decimal
        if (is_numeric($input)) {
            return (float) $input;
        }

        return null;
    }
}
