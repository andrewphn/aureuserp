<?php

namespace Webkul\Project\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Complexity Scoring Settings
 *
 * Admin-configurable settings for the complexity scoring system.
 * These values were previously hardcoded in ComplexityScoreService.
 */
class ComplexityScoringSettings extends Settings
{
    /**
     * Base complexity scores for each component type.
     * Keys: door, drawer, shelf_fixed, shelf_adjustable, shelf_roll_out, shelf_pull_down, pullout
     */
    public array $base_scores;

    /**
     * Component type weights for weighted average calculations.
     * Higher weight = more impact on parent complexity score.
     * Keys: door, drawer, shelf, pullout
     */
    public array $component_weights;

    /**
     * Modification points for various upgrades and features.
     * Keys: soft_close, hinge_euro_concealed, has_glass, joinery_dovetail, etc.
     */
    public array $modification_points;

    /**
     * Score thresholds for complexity labels.
     * Keys: simple, standard, moderate, complex, very_complex
     */
    public array $score_thresholds;

    /**
     * Standard door widths (inches) - non-standard adds complexity.
     */
    public array $standard_door_widths;

    /**
     * Standard door heights (inches) - non-standard adds complexity.
     */
    public array $standard_door_heights;

    /**
     * Standard drawer widths (inches) - non-standard adds complexity.
     */
    public array $standard_drawer_widths;

    /**
     * Settings group name.
     */
    public static function group(): string
    {
        return 'complexity_scoring';
    }
}
