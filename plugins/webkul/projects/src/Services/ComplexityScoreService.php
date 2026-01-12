<?php

namespace Webkul\Project\Services;

use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Pullout;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Settings\ComplexityScoringSettings;

/**
 * Service for calculating hierarchical complexity scores.
 *
 * Complexity scores cascade from components upward:
 * Component (Door/Drawer/Shelf/Pullout) → Section → Cabinet → CabinetRun → RoomLocation → Room → Project
 *
 * Uses weighted averages for aggregation where component types have different weights.
 * Configuration is loaded from ComplexityScoringSettings (admin-configurable).
 */
class ComplexityScoreService
{
    /**
     * Cached settings instance.
     */
    protected ?ComplexityScoringSettings $settings = null;

    /**
     * Default base scores (fallback if settings not configured).
     */
    protected const DEFAULT_BASE_SCORES = [
        'door' => 10,
        'drawer' => 15,
        'shelf_fixed' => 5,
        'shelf_adjustable' => 7,
        'shelf_roll_out' => 13,
        'shelf_pull_down' => 15,
        'pullout' => 20,
    ];

    /**
     * Default component weights (fallback).
     */
    protected const DEFAULT_COMPONENT_WEIGHTS = [
        'door' => 1.0,
        'drawer' => 1.2,
        'shelf' => 0.5,
        'pullout' => 1.5,
    ];

    /**
     * Default modification points (fallback).
     */
    protected const DEFAULT_MODIFICATION_POINTS = [
        'soft_close' => 3,
        'hinge_euro_concealed' => 2,
        'hinge_specialty' => 4,
        'slide_blum_tandem' => 3,
        'slide_full_extension' => 2,
        'slide_undermount' => 2,
        'has_glass' => 8,
        'glass_mullioned' => 12,
        'glass_leaded' => 15,
        'joinery_dovetail' => 6,
        'joinery_dado' => 2,
        'joinery_finger' => 4,
        'has_check_rail' => 4,
        'profile_beaded' => 4,
        'profile_raised_panel' => 5,
        'profile_shaker' => 2,
        'profile_slab' => 0,
        'fabrication_five_piece' => 3,
        'non_standard_width' => 3,
        'non_standard_height' => 3,
        'non_standard_depth' => 2,
        'shelf_roll_out' => 8,
        'shelf_pull_down' => 10,
        'shelf_corner' => 5,
        'shelf_floating' => 4,
        'pullout_trash' => 0,
        'pullout_spice_rack' => 3,
        'pullout_lazy_susan' => 8,
        'pullout_mixer_lift' => 10,
        'pullout_blind_corner' => 6,
        'pullout_pantry' => 5,
    ];

    /**
     * Default score thresholds (fallback).
     */
    protected const DEFAULT_SCORE_THRESHOLDS = [
        'simple' => 10,
        'standard' => 15,
        'moderate' => 20,
        'complex' => 30,
        'very_complex' => 40,
    ];

    /**
     * Default standard dimensions (fallback).
     */
    protected const DEFAULT_STANDARD_DOOR_WIDTHS = [12, 15, 18, 21, 24, 27, 30, 33, 36];
    protected const DEFAULT_STANDARD_DOOR_HEIGHTS = [30, 36, 42];
    protected const DEFAULT_STANDARD_DRAWER_WIDTHS = [12, 15, 18, 21, 24, 27, 30, 33, 36];

    /**
     * Get settings instance (cached).
     */
    protected function getSettings(): ?ComplexityScoringSettings
    {
        if ($this->settings === null) {
            try {
                $this->settings = app(ComplexityScoringSettings::class);
            } catch (\Exception $e) {
                // Settings not yet migrated, return null
                return null;
            }
        }

        return $this->settings;
    }

    /**
     * Get base scores from settings or defaults.
     */
    protected function getBaseScores(): array
    {
        $settings = $this->getSettings();

        return $settings?->base_scores ?? self::DEFAULT_BASE_SCORES;
    }

    /**
     * Get component weights from settings or defaults.
     */
    protected function getComponentWeights(): array
    {
        $settings = $this->getSettings();

        return $settings?->component_weights ?? self::DEFAULT_COMPONENT_WEIGHTS;
    }

    /**
     * Get modification points from settings or defaults.
     */
    protected function getModificationPoints(): array
    {
        $settings = $this->getSettings();

        return $settings?->modification_points ?? self::DEFAULT_MODIFICATION_POINTS;
    }

    /**
     * Get score thresholds from settings or defaults.
     */
    protected function getScoreThresholds(): array
    {
        $settings = $this->getSettings();

        return $settings?->score_thresholds ?? self::DEFAULT_SCORE_THRESHOLDS;
    }

    /**
     * Get standard door widths from settings or defaults.
     */
    protected function getStandardDoorWidths(): array
    {
        $settings = $this->getSettings();
        $widths = $settings?->standard_door_widths ?? self::DEFAULT_STANDARD_DOOR_WIDTHS;

        // Convert string values to floats if needed (TagsInput stores as strings)
        return array_map('floatval', $widths);
    }

    /**
     * Get standard door heights from settings or defaults.
     */
    protected function getStandardDoorHeights(): array
    {
        $settings = $this->getSettings();
        $heights = $settings?->standard_door_heights ?? self::DEFAULT_STANDARD_DOOR_HEIGHTS;

        return array_map('floatval', $heights);
    }

    /**
     * Get standard drawer widths from settings or defaults.
     */
    protected function getStandardDrawerWidths(): array
    {
        $settings = $this->getSettings();
        $widths = $settings?->standard_drawer_widths ?? self::DEFAULT_STANDARD_DRAWER_WIDTHS;

        return array_map('floatval', $widths);
    }

    /**
     * Calculate complexity score for a Door component.
     */
    public function calculateDoorComplexity(Door $door): array
    {
        $baseScores = $this->getBaseScores();
        $modPoints = $this->getModificationPoints();

        $breakdown = [];
        $score = $baseScores['door'] ?? 10;
        $breakdown['base'] = $score;

        // Hardware upgrades
        if ($door->hinge_type === 'euro_concealed') {
            $pts = $modPoints['hinge_euro_concealed'] ?? 2;
            $score += $pts;
            $breakdown['hinge_euro_concealed'] = $pts;
        } elseif (in_array($door->hinge_type, ['specialty', 'pivot', 'hidden'])) {
            $pts = $modPoints['hinge_specialty'] ?? 4;
            $score += $pts;
            $breakdown['hinge_specialty'] = $pts;
        }

        // Glass features
        if ($door->has_glass) {
            $pts = $modPoints['has_glass'] ?? 8;
            $score += $pts;
            $breakdown['has_glass'] = $pts;

            if ($door->glass_type === 'mullioned') {
                $pts = $modPoints['glass_mullioned'] ?? 12;
                $score += $pts;
                $breakdown['glass_mullioned'] = $pts;
            } elseif ($door->glass_type === 'leaded') {
                $pts = $modPoints['glass_leaded'] ?? 15;
                $score += $pts;
                $breakdown['glass_leaded'] = $pts;
            }
        }

        // Profile complexity
        if ($door->profile_type === 'beaded') {
            $pts = $modPoints['profile_beaded'] ?? 4;
            $score += $pts;
            $breakdown['profile_beaded'] = $pts;
        } elseif ($door->profile_type === 'raised_panel') {
            $pts = $modPoints['profile_raised_panel'] ?? 5;
            $score += $pts;
            $breakdown['profile_raised_panel'] = $pts;
        } elseif ($door->profile_type === 'shaker') {
            $pts = $modPoints['profile_shaker'] ?? 2;
            $score += $pts;
            $breakdown['profile_shaker'] = $pts;
        }

        // Check rail
        if ($door->has_check_rail) {
            $pts = $modPoints['has_check_rail'] ?? 4;
            $score += $pts;
            $breakdown['has_check_rail'] = $pts;
        }

        // Custom dimensions check
        if ($door->width_inches && ! $this->isStandardDimension($door->width_inches, $this->getStandardDoorWidths())) {
            $pts = $modPoints['non_standard_width'] ?? 3;
            $score += $pts;
            $breakdown['non_standard_width'] = $pts;
        }

        if ($door->height_inches && ! $this->isStandardDimension($door->height_inches, $this->getStandardDoorHeights())) {
            $pts = $modPoints['non_standard_height'] ?? 3;
            $score += $pts;
            $breakdown['non_standard_height'] = $pts;
        }

        return [
            'score' => round($score, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate complexity score for a Drawer component.
     */
    public function calculateDrawerComplexity(Drawer $drawer): array
    {
        $baseScores = $this->getBaseScores();
        $modPoints = $this->getModificationPoints();

        $breakdown = [];
        $score = $baseScores['drawer'] ?? 15;
        $breakdown['base'] = $score;

        // Soft-close hardware
        if ($drawer->soft_close) {
            $pts = $modPoints['soft_close'] ?? 3;
            $score += $pts;
            $breakdown['soft_close'] = $pts;
        }

        // Slide type
        if (str_contains(strtolower($drawer->slide_type ?? ''), 'blum') ||
            str_contains(strtolower($drawer->slide_type ?? ''), 'tandem')) {
            $pts = $modPoints['slide_blum_tandem'] ?? 3;
            $score += $pts;
            $breakdown['slide_blum_tandem'] = $pts;
        } elseif (str_contains(strtolower($drawer->slide_type ?? ''), 'full_extension') ||
                  str_contains(strtolower($drawer->slide_type ?? ''), 'full-extension')) {
            $pts = $modPoints['slide_full_extension'] ?? 2;
            $score += $pts;
            $breakdown['slide_full_extension'] = $pts;
        } elseif (str_contains(strtolower($drawer->slide_type ?? ''), 'undermount')) {
            $pts = $modPoints['slide_undermount'] ?? 2;
            $score += $pts;
            $breakdown['slide_undermount'] = $pts;
        }

        // Joinery method
        if ($drawer->joinery_method === 'dovetail') {
            $pts = $modPoints['joinery_dovetail'] ?? 6;
            $score += $pts;
            $breakdown['joinery_dovetail'] = $pts;
        } elseif ($drawer->joinery_method === 'finger') {
            $pts = $modPoints['joinery_finger'] ?? 4;
            $score += $pts;
            $breakdown['joinery_finger'] = $pts;
        } elseif ($drawer->joinery_method === 'dado') {
            $pts = $modPoints['joinery_dado'] ?? 2;
            $score += $pts;
            $breakdown['joinery_dado'] = $pts;
        }

        // Custom dimensions
        if ($drawer->front_width_inches && ! $this->isStandardDimension($drawer->front_width_inches, $this->getStandardDrawerWidths())) {
            $pts = $modPoints['non_standard_width'] ?? 3;
            $score += $pts;
            $breakdown['non_standard_width'] = $pts;
        }

        return [
            'score' => round($score, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate complexity score for a Shelf component.
     */
    public function calculateShelfComplexity(Shelf $shelf): array
    {
        $baseScores = $this->getBaseScores();
        $modPoints = $this->getModificationPoints();

        $breakdown = [];

        // Base score depends on shelf type
        $shelfType = $shelf->shelf_type ?? 'fixed';
        $baseKey = match ($shelfType) {
            'adjustable' => 'shelf_adjustable',
            'roll_out', 'roll-out' => 'shelf_roll_out',
            'pull_down', 'pull-down' => 'shelf_pull_down',
            default => 'shelf_fixed',
        };

        $score = $baseScores[$baseKey] ?? $baseScores['shelf_fixed'] ?? 5;
        $breakdown['base'] = $score;

        // Soft-close for roll-out shelves
        if ($shelf->soft_close) {
            $pts = $modPoints['soft_close'] ?? 3;
            $score += $pts;
            $breakdown['soft_close'] = $pts;
        }

        // Corner shelves add complexity
        if ($shelfType === 'corner') {
            $pts = $modPoints['shelf_corner'] ?? 5;
            $score += $pts;
            $breakdown['shelf_corner'] = $pts;
        }

        // Floating shelves
        if ($shelfType === 'floating') {
            $pts = $modPoints['shelf_floating'] ?? 4;
            $score += $pts;
            $breakdown['shelf_floating'] = $pts;
        }

        // Slide type for roll-out shelves
        if ($shelf->slide_type && str_contains(strtolower($shelf->slide_type), 'blum')) {
            $pts = $modPoints['slide_blum_tandem'] ?? 3;
            $score += $pts;
            $breakdown['slide_blum_tandem'] = $pts;
        }

        return [
            'score' => round($score, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate complexity score for a Pullout component.
     */
    public function calculatePulloutComplexity(Pullout $pullout): array
    {
        $baseScores = $this->getBaseScores();
        $modPoints = $this->getModificationPoints();

        $breakdown = [];
        $score = $baseScores['pullout'] ?? 20;
        $breakdown['base'] = $score;

        // Pullout type adds specific complexity
        $pulloutType = $pullout->pullout_type ?? 'other';
        $typeKey = 'pullout_'.str_replace('-', '_', $pulloutType);

        if (isset($modPoints[$typeKey])) {
            $score += $modPoints[$typeKey];
            $breakdown[$typeKey] = $modPoints[$typeKey];
        }

        // Soft-close
        if ($pullout->soft_close) {
            $pts = $modPoints['soft_close'] ?? 3;
            $score += $pts;
            $breakdown['soft_close'] = $pts;
        }

        // Custom dimensions
        if ($pullout->width_inches && $pullout->width_inches > 24) {
            $pts = $modPoints['non_standard_width'] ?? 3;
            $score += $pts;
            $breakdown['non_standard_width'] = $pts;
        }

        return [
            'score' => round($score, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate weighted average complexity for a CabinetSection.
     */
    public function calculateSectionComplexity(CabinetSection $section): array
    {
        $weights = $this->getComponentWeights();

        $weightedSum = 0;
        $totalWeight = 0;
        $components = [];

        // Aggregate doors
        foreach ($section->doors ?? [] as $door) {
            $weight = $weights['door'] ?? 1.0;
            $score = $door->complexity_score ?? 0;
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
            $components[] = ['type' => 'door', 'id' => $door->id, 'score' => $score];
        }

        // Aggregate drawers
        foreach ($section->drawers ?? [] as $drawer) {
            $weight = $weights['drawer'] ?? 1.2;
            $score = $drawer->complexity_score ?? 0;
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
            $components[] = ['type' => 'drawer', 'id' => $drawer->id, 'score' => $score];
        }

        // Aggregate shelves
        foreach ($section->shelves ?? [] as $shelf) {
            $weight = $weights['shelf'] ?? 0.5;
            $score = $shelf->complexity_score ?? 0;
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
            $components[] = ['type' => 'shelf', 'id' => $shelf->id, 'score' => $score];
        }

        // Aggregate pullouts
        foreach ($section->pullouts ?? [] as $pullout) {
            $weight = $weights['pullout'] ?? 1.5;
            $score = $pullout->complexity_score ?? 0;
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
            $components[] = ['type' => 'pullout', 'id' => $pullout->id, 'score' => $score];
        }

        $score = $totalWeight > 0 ? $weightedSum / $totalWeight : 0;

        return [
            'score' => round($score, 2),
            'breakdown' => ['components' => $components],
            'component_count' => count($components),
        ];
    }

    /**
     * Calculate weighted average complexity for a Cabinet.
     */
    public function calculateCabinetComplexity(Cabinet $cabinet): array
    {
        $sections = $cabinet->sections ?? collect();
        $sectionScores = [];
        $totalScore = 0;

        foreach ($sections as $section) {
            $score = $section->complexity_score ?? 0;
            $totalScore += $score;
            $sectionScores[] = ['id' => $section->id, 'code' => $section->section_code, 'score' => $score];
        }

        $count = count($sectionScores);
        $avgScore = $count > 0 ? $totalScore / $count : 0;

        return [
            'score' => round($avgScore, 2),
            'breakdown' => ['sections' => $sectionScores],
            'section_count' => $count,
        ];
    }

    /**
     * Calculate weighted average complexity for a CabinetRun.
     */
    public function calculateCabinetRunComplexity(CabinetRun $run): array
    {
        $cabinets = $run->cabinets ?? collect();
        $cabinetScores = [];
        $totalScore = 0;

        foreach ($cabinets as $cabinet) {
            $score = $cabinet->complexity_score ?? 0;
            $totalScore += $score;
            $cabinetScores[] = ['id' => $cabinet->id, 'number' => $cabinet->cabinet_number, 'score' => $score];
        }

        $count = count($cabinetScores);
        $avgScore = $count > 0 ? $totalScore / $count : 0;

        return [
            'score' => round($avgScore, 2),
            'breakdown' => ['cabinets' => $cabinetScores],
            'cabinet_count' => $count,
        ];
    }

    /**
     * Calculate weighted average complexity for a RoomLocation.
     */
    public function calculateLocationComplexity(RoomLocation $location): array
    {
        $runs = $location->cabinetRuns ?? collect();
        $runScores = [];
        $totalScore = 0;

        foreach ($runs as $run) {
            $score = $run->complexity_score ?? 0;
            $totalScore += $score;
            $runScores[] = ['id' => $run->id, 'name' => $run->name, 'score' => $score];
        }

        $count = count($runScores);
        $avgScore = $count > 0 ? $totalScore / $count : 0;

        return [
            'score' => round($avgScore, 2),
            'breakdown' => ['runs' => $runScores],
            'run_count' => $count,
        ];
    }

    /**
     * Calculate weighted average complexity for a Room.
     */
    public function calculateRoomComplexity(Room $room): array
    {
        $locations = $room->locations ?? collect();
        $locationScores = [];
        $totalScore = 0;

        foreach ($locations as $location) {
            $score = $location->complexity_score ?? 0;
            $totalScore += $score;
            $locationScores[] = ['id' => $location->id, 'name' => $location->name, 'score' => $score];
        }

        $count = count($locationScores);
        $avgScore = $count > 0 ? $totalScore / $count : 0;

        return [
            'score' => round($avgScore, 2),
            'breakdown' => ['locations' => $locationScores],
            'location_count' => $count,
        ];
    }

    /**
     * Calculate weighted average complexity for a Project.
     */
    public function calculateProjectComplexity(Project $project): array
    {
        $rooms = $project->rooms ?? collect();
        $roomScores = [];
        $totalScore = 0;

        foreach ($rooms as $room) {
            $score = $room->complexity_score ?? 0;
            $totalScore += $score;
            $roomScores[] = ['id' => $room->id, 'name' => $room->name, 'score' => $score];
        }

        $count = count($roomScores);
        $avgScore = $count > 0 ? $totalScore / $count : 0;

        return [
            'score' => round($avgScore, 2),
            'breakdown' => ['rooms' => $roomScores],
            'room_count' => $count,
        ];
    }

    /**
     * Recalculate and persist complexity score for any entity.
     */
    public function recalculateAndSave(Model $entity): void
    {
        $result = match (true) {
            $entity instanceof Door => $this->calculateDoorComplexity($entity),
            $entity instanceof Drawer => $this->calculateDrawerComplexity($entity),
            $entity instanceof Shelf => $this->calculateShelfComplexity($entity),
            $entity instanceof Pullout => $this->calculatePulloutComplexity($entity),
            $entity instanceof CabinetSection => $this->calculateSectionComplexity($entity),
            $entity instanceof Cabinet => $this->calculateCabinetComplexity($entity),
            $entity instanceof CabinetRun => $this->calculateCabinetRunComplexity($entity),
            $entity instanceof RoomLocation => $this->calculateLocationComplexity($entity),
            $entity instanceof Room => $this->calculateRoomComplexity($entity),
            $entity instanceof Project => $this->calculateProjectComplexity($entity),
            default => throw new \InvalidArgumentException('Unsupported entity type: '.get_class($entity)),
        };

        $entity->complexity_score = $result['score'];
        $entity->complexity_breakdown = $result['breakdown'];
        $entity->complexity_calculated_at = now();

        // Set cached counts if applicable
        if (isset($result['component_count'])) {
            $entity->component_count_cached = $result['component_count'];
        }
        if (isset($result['section_count'])) {
            $entity->section_count_cached = $result['section_count'];
        }
        if (isset($result['cabinet_count'])) {
            $entity->cabinet_count_cached = $result['cabinet_count'];
        }
        if (isset($result['run_count'])) {
            $entity->run_count_cached = $result['run_count'];
        }
        if (isset($result['location_count'])) {
            $entity->location_count_cached = $result['location_count'];
        }
        if (isset($result['room_count'])) {
            $entity->room_count_cached = $result['room_count'];
        }

        $entity->saveQuietly();
    }

    /**
     * Cascade recalculation up the hierarchy from a given entity.
     */
    public function cascadeRecalculation(Model $entity): void
    {
        $this->recalculateAndSave($entity);

        // Cascade upward based on entity type
        if ($entity instanceof Door || $entity instanceof Drawer ||
            $entity instanceof Shelf || $entity instanceof Pullout) {
            // Components cascade to section or cabinet
            if ($entity->section) {
                $this->cascadeRecalculation($entity->section);
            } elseif ($entity->cabinet) {
                $this->cascadeRecalculation($entity->cabinet);
            }
        } elseif ($entity instanceof CabinetSection) {
            if ($entity->cabinet) {
                $this->cascadeRecalculation($entity->cabinet);
            }
        } elseif ($entity instanceof Cabinet) {
            if ($entity->cabinetRun) {
                $this->cascadeRecalculation($entity->cabinetRun);
            }
        } elseif ($entity instanceof CabinetRun) {
            if ($entity->roomLocation) {
                $this->cascadeRecalculation($entity->roomLocation);
            }
        } elseif ($entity instanceof RoomLocation) {
            if ($entity->room) {
                $this->cascadeRecalculation($entity->room);
            }
        } elseif ($entity instanceof Room) {
            if ($entity->project) {
                $this->cascadeRecalculation($entity->project);
            }
        }
        // Project is the top level, nothing to cascade to
    }

    /**
     * Check if a dimension is within a tolerance of standard values.
     */
    protected function isStandardDimension(float $value, array $standards, float $tolerance = 0.5): bool
    {
        foreach ($standards as $standard) {
            if (abs($value - $standard) <= $tolerance) {
                return true;
            }
        }

        return false;
    }

    /**
     * Map complexity score to a production time multiplier.
     *
     * Used by the capacity widget to adjust estimated hours.
     */
    public function scoreToMultiplier(float $score): float
    {
        $thresholds = $this->getScoreThresholds();

        return match (true) {
            $score < ($thresholds['simple'] ?? 10) => 0.8,      // Simple
            $score < ($thresholds['standard'] ?? 15) => 1.0,    // Standard
            $score < ($thresholds['moderate'] ?? 20) => 1.2,    // Moderate
            $score < ($thresholds['complex'] ?? 30) => 1.4,     // Complex
            $score < ($thresholds['very_complex'] ?? 40) => 1.6, // Very Complex
            default => 1.8,                                      // Custom/Specialty
        };
    }

    /**
     * Get human-readable complexity label.
     */
    public function scoreToLabel(float $score): string
    {
        $thresholds = $this->getScoreThresholds();

        return match (true) {
            $score < ($thresholds['simple'] ?? 10) => 'Simple',
            $score < ($thresholds['standard'] ?? 15) => 'Standard',
            $score < ($thresholds['moderate'] ?? 20) => 'Moderate',
            $score < ($thresholds['complex'] ?? 30) => 'Complex',
            $score < ($thresholds['very_complex'] ?? 40) => 'Very Complex',
            default => 'Custom',
        };
    }

    /**
     * Get color class for complexity display.
     */
    public function scoreToColor(float $score): string
    {
        $thresholds = $this->getScoreThresholds();

        return match (true) {
            $score < ($thresholds['simple'] ?? 10) => 'success',
            $score < ($thresholds['standard'] ?? 15) => 'info',
            $score < ($thresholds['moderate'] ?? 20) => 'primary',
            $score < ($thresholds['complex'] ?? 30) => 'warning',
            default => 'danger',
        };
    }

    /**
     * Clear cached settings (useful after admin updates settings).
     */
    public function clearSettingsCache(): void
    {
        $this->settings = null;
    }
}
