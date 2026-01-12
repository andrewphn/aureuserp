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
}
