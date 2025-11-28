<?php

namespace Webkul\Project\Utils;

class PositionInferenceUtil
{
    /**
     * Auto-detect cabinet position from Y coordinate on page
     *
     * @param float $normalizedY Y coordinate (normalized 0-1)
     * @param float $normalizedHeight Height (normalized 0-1)
     * @return array ['inferred_position' => string, 'vertical_zone' => string]
     */
    public static function inferPositionFromCoordinates(float $normalizedY, float $normalizedHeight): array
    {
        // Convert normalized Y to percentage (flip Y axis for typical drawing orientation)
        $yPercent = (1 - $normalizedY) * 100;

        // Determine vertical zone based on Y position
        // Note: In PDF coordinates, Y=0 is at bottom, so we flip to get standard top=0 orientation
        if ($yPercent < 30) {
            $zone = 'upper';
            $position = 'wall_cabinet';
        } elseif ($yPercent > 70) {
            $zone = 'lower';
            $position = 'base_cabinet';
        } else {
            $zone = 'middle';

            // Check height to determine if it's a tall cabinet or standard base
            $heightPercent = $normalizedHeight * 100;

            if ($heightPercent > 40) {
                $position = 'tall_cabinet';
            } else {
                $position = 'base_cabinet';
            }
        }

        return [
            'inferred_position' => $position,
            'vertical_zone'     => $zone,
        ];
    }
}
