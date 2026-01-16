<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Faceframe;
use Webkul\Project\Models\ConstructionTemplate;

/**
 * Rails and Stiles Configurator Service
 *
 * Central service for determining when face frame rails and stiles are needed.
 * This service is used by FaceFrameConfiguratorService, DrawerConfiguratorService,
 * and other services that need to understand face frame component requirements.
 *
 * TCS Construction Rules (Levi, Jan 2025):
 *
 * RAILS (horizontal pieces):
 *   - TOP RAIL: Almost always present (exception: full-height opening extending to top)
 *   - BOTTOM RAIL: Present UNLESS sink base, appliance opening, or full-height opening
 *   - INTERMEDIATE RAILS: Between separate openings (drawers, doors)
 *
 * STILES (vertical pieces):
 *   - LEFT + RIGHT: Always present
 *   - CENTER STILES: For double doors or horizontal divisions
 *
 * KEY RULE: False front backing replaces STRETCHER (inside box), NOT rails (on face frame)
 */
class RailsAndStilesConfiguratorService
{
    /**
     * Standard dimensions (inches)
     */
    public const STANDARD_STILE_WIDTH = 1.5;
    public const WIDE_STILE_WIDTH = 1.75;
    public const STANDARD_RAIL_WIDTH = 1.5;

    /**
     * Cabinet types that SHOULD have a bottom rail
     *
     * TCS Standard: Most cabinets do NOT have a bottom rail.
     * The cabinet bottom panel serves that function.
     *
     * Bottom rail is only used for specific cabinet types where
     * there's no bottom panel at the face frame level.
     */
    public const HAS_BOTTOM_RAIL_TYPES = [
        // Add specific types that need bottom rail here
        // Most TCS cabinets do NOT have bottom rail
    ];

    /**
     * @deprecated Bottom rail is NOT standard at TCS
     * Kept for reference - cabinet bottom panel serves this function
     */
    public const NO_BOTTOM_RAIL_TYPES = [
        'sink_base',
        'base_sink',
        'vanity_sink',
        'kitchen_sink',
        'appliance_opening',
        'dishwasher_opening',
    ];

    /**
     * Cabinet types that are frameless (no face frame at all)
     */
    public const FRAMELESS_TYPES = [
        'closet_unit',
        'wardrobe',
        'euro_base',
        'frameless',
    ];

    /**
     * Generate complete rails and stiles configuration for a cabinet
     *
     * @param Cabinet $cabinet
     * @return array Configuration with counts, dimensions, and component list
     */
    public function generateConfiguration(Cabinet $cabinet): array
    {
        // Check if cabinet uses face frame construction
        if (!$this->hasFaceFrame($cabinet)) {
            return [
                'has_face_frame' => false,
                'construction_style' => 'frameless',
                'stiles' => ['count' => 0],
                'rails' => ['count' => 0],
                'components' => [],
            ];
        }

        // Get effective dimensions (from cabinet, run faceframe, or template)
        $config = $this->getEffectiveDimensions($cabinet);

        // Calculate component requirements
        $stileConfig = $this->calculateStiles($cabinet, $config);
        $railConfig = $this->calculateRails($cabinet, $config);

        // Generate component list for cut list
        $components = $this->generateComponentList($cabinet, $stileConfig, $railConfig, $config);

        return [
            'has_face_frame' => true,
            'construction_style' => 'face_frame',
            'cabinet_type' => $cabinet->cabinet_type ?? 'base',
            'dimensions' => $config,
            'stiles' => $stileConfig,
            'rails' => $railConfig,
            'components' => $components,
            'summary' => [
                'total_stile_count' => $stileConfig['count'],
                'total_rail_count' => $railConfig['count'],
                'has_bottom_rail' => $railConfig['has_bottom'],
                'intermediate_rail_count' => $railConfig['intermediate_count'],
                'center_stile_count' => $stileConfig['center_count'],
            ],
        ];
    }

    /**
     * Check if cabinet type uses face frame construction
     *
     * @param Cabinet $cabinet
     * @return bool
     */
    public function hasFaceFrame(Cabinet $cabinet): bool
    {
        $constructionStyle = $cabinet->construction_style ?? 'face_frame';

        if ($constructionStyle === 'frameless') {
            return false;
        }

        $cabinetType = strtolower($cabinet->cabinet_type ?? 'base');

        return !in_array($cabinetType, self::FRAMELESS_TYPES);
    }

    /**
     * Check if cabinet type should have a bottom rail
     *
     * TCS Standard: Most cabinets do NOT have a bottom rail.
     * The cabinet bottom panel serves that structural function.
     * Bottom rail is only added for specific cabinet types.
     *
     * @param Cabinet $cabinet
     * @return bool
     */
    public function hasBottomRail(Cabinet $cabinet): bool
    {
        // Check explicit override flag - if set to true, force bottom rail
        if ($cabinet->has_bottom_rail ?? false) {
            return true;
        }

        $cabinetType = strtolower($cabinet->cabinet_type ?? 'base');

        // TCS Standard: Most cabinets do NOT have bottom rail
        // Only specific types that need it are in HAS_BOTTOM_RAIL_TYPES
        return in_array($cabinetType, self::HAS_BOTTOM_RAIL_TYPES);
    }

    /**
     * Check if cabinet should have a top rail
     *
     * @param Cabinet $cabinet
     * @return bool
     */
    public function hasTopRail(Cabinet $cabinet): bool
    {
        // Top rail almost always present
        // Only exception: full-height appliance opening extending to top
        if (($cabinet->is_full_height_opening ?? false) &&
            ($cabinet->opening_extends_to_top ?? false)) {
            return false;
        }

        return true;
    }

    /**
     * Get effective dimensions from inheritance chain
     * Cabinet → Run Faceframe → Construction Template → Defaults
     *
     * @param Cabinet $cabinet
     * @return array
     */
    protected function getEffectiveDimensions(Cabinet $cabinet): array
    {
        // Get run faceframe (if exists)
        $runFaceframe = $cabinet->cabinetRun?->faceframe;

        // Get construction template
        $template = $cabinet->constructionTemplate
            ?? $cabinet->room?->constructionTemplate
            ?? $cabinet->room?->project?->constructionTemplate
            ?? ConstructionTemplate::where('is_default', true)->first();

        // Resolve with inheritance
        return [
            'stile_width' => $cabinet->face_frame_stile_width_inches
                ?? $runFaceframe?->stile_width
                ?? $template?->face_frame_stile_width
                ?? self::STANDARD_STILE_WIDTH,

            'rail_width' => $cabinet->face_frame_rail_width_inches
                ?? $runFaceframe?->rail_width
                ?? $template?->face_frame_rail_width
                ?? self::STANDARD_RAIL_WIDTH,

            'thickness' => $runFaceframe?->material_thickness
                ?? $template?->face_frame_thickness
                ?? 0.75,

            'cabinet_width' => $cabinet->length_inches ?? $cabinet->width_inches ?? 24,
            'cabinet_height' => $cabinet->height_inches ?? 30,
            'toe_kick_height' => $cabinet->toe_kick_height_inches ?? 4.0,
            'box_height' => ($cabinet->height_inches ?? 30) - ($cabinet->toe_kick_height_inches ?? 4.0),
        ];
    }

    /**
     * Calculate stile requirements
     *
     * RULES:
     * - Left + Right: Always present
     * - Center stiles: For double doors or horizontal divisions
     * - Fixed dividers create matching stiles
     *
     * @param Cabinet $cabinet
     * @param array $config
     * @return array
     */
    protected function calculateStiles(Cabinet $cabinet, array $config): array
    {
        $leftStile = true;  // Always
        $rightStile = true; // Always

        $doorCount = $cabinet->door_count ?? 0;
        $centerStileCount = 0;

        // Center stiles for paired doors (2 doors = 1 center stile, 4 doors = 2)
        if ($doorCount >= 2) {
            $centerStileCount = (int) floor($doorCount / 2);
        }

        // Override from cabinet if explicitly set
        if ($cabinet->face_frame_mid_stile_count !== null) {
            $centerStileCount = (int) $cabinet->face_frame_mid_stile_count;
        }

        // Add stiles for fixed dividers
        $dividerCount = 0;
        if (method_exists($cabinet, 'fixedDividers')) {
            $dividerCount = $cabinet->fixedDividers()->count();
        }
        $centerStileCount += $dividerCount;

        $totalCount = 2 + $centerStileCount; // Left + Right + Centers

        return [
            'has_left' => $leftStile,
            'has_right' => $rightStile,
            'center_count' => $centerStileCount,
            'divider_stile_count' => $dividerCount,
            'count' => $totalCount,
            'width' => $config['stile_width'],
            'length' => $config['box_height'],
        ];
    }

    /**
     * Calculate rail requirements
     *
     * RULES:
     * - Top rail: Almost always (exception: full-height opening to top)
     * - Bottom rail: YES unless sink base or full-height opening
     * - Intermediate rails: Between separate openings
     *   - N drawers = N-1 rails between them
     *   - Drawer section + Door section = 1 rail between
     *   - False front at top of door opening = NO extra rail (same opening zone)
     *   - False front above drawer = 1 rail between them
     *
     * @param Cabinet $cabinet
     * @param array $config
     * @return array
     */
    protected function calculateRails(Cabinet $cabinet, array $config): array
    {
        $hasTop = $this->hasTopRail($cabinet);
        $hasBottom = $this->hasBottomRail($cabinet);

        // Calculate intermediate rails
        $intermediateCount = $this->calculateIntermediateRails($cabinet);

        // Calculate rail length (spans between outer stiles)
        $stileWidth = $config['stile_width'];
        $cabinetWidth = $config['cabinet_width'];
        $railLength = $cabinetWidth - (2 * $stileWidth);

        $totalCount = ($hasTop ? 1 : 0) + ($hasBottom ? 1 : 0) + $intermediateCount;

        return [
            'has_top' => $hasTop,
            'has_bottom' => $hasBottom,
            'intermediate_count' => $intermediateCount,
            'count' => $totalCount,
            'width' => $config['rail_width'],
            'length' => $railLength,
        ];
    }

    /**
     * Calculate intermediate rail count
     *
     * RULES from Levi:
     * - N drawers = (N-1) rails between them
     * - N false fronts stacked = (N-1) rails between them
     * - Drawer section + Door section = 1 rail between
     * - False front ABOVE doors = NO rail (same opening zone)
     * - False front ABOVE drawer = 1 rail (separate openings)
     *
     * @param Cabinet $cabinet
     * @return int
     */
    public function calculateIntermediateRails(Cabinet $cabinet): int
    {
        $railCount = 0;

        $drawerCount = $cabinet->drawer_count ?? 0;
        $doorCount = $cabinet->door_count ?? 0;

        // Get false fronts
        $falseFronts = [];
        if (method_exists($cabinet, 'falseFronts')) {
            $falseFronts = $cabinet->falseFronts()->orderBy('position')->get()->toArray();
        }
        $falseFrontCount = count($falseFronts);

        // Rule 1: Rails between drawers (N drawers = N-1 rails)
        if ($drawerCount > 1) {
            $railCount += ($drawerCount - 1);
        }

        // Rule 2: Rails between stacked false fronts (rare, but follows same logic)
        if ($falseFrontCount > 1) {
            $railCount += ($falseFrontCount - 1);
        }

        // Rule 3: Rail between drawer section and door section
        $hasDrawerSection = ($drawerCount > 0);
        $hasDoorSection = ($doorCount > 0);

        if ($hasDrawerSection && $hasDoorSection) {
            $railCount += 1;
        }

        // Rule 4: False front above drawers = 1 rail between
        // (False front above doors = NO rail - same opening zone)
        if ($falseFrontCount > 0 && $drawerCount > 0 && $doorCount == 0) {
            // False front + drawers only (no doors) = rail between FF and drawers
            $railCount += 1;
        }

        // Note: False front + doors (no drawers) = NO additional rail
        // The false front sits at top of the door opening zone

        return $railCount;
    }

    /**
     * Determine what's at each opening position (for detailed layout)
     *
     * @param Cabinet $cabinet
     * @return array List of openings from top to bottom
     */
    public function getOpeningLayout(Cabinet $cabinet): array
    {
        $openings = [];

        // Get false fronts (typically at top)
        if (method_exists($cabinet, 'falseFronts')) {
            foreach ($cabinet->falseFronts()->orderBy('position')->get() as $ff) {
                $openings[] = [
                    'type' => 'false_front',
                    'model' => $ff,
                    'position' => $ff->position ?? 'top',
                    'height_inches' => $ff->height_inches,
                    'needs_rail_below' => false, // Will be determined by what's below
                ];
            }
        }

        // Get drawers
        if (method_exists($cabinet, 'drawers')) {
            foreach ($cabinet->drawers()->orderBy('position')->get() as $i => $drawer) {
                $openings[] = [
                    'type' => 'drawer',
                    'model' => $drawer,
                    'position' => $drawer->position ?? $i,
                    'height_inches' => $drawer->front_height_inches,
                    'needs_rail_below' => true, // Drawers need rail between them
                ];
            }
        }

        // Doors fill the remaining space (conceptually at bottom for base cabinets)
        $doorCount = $cabinet->door_count ?? 0;
        if ($doorCount > 0) {
            $openings[] = [
                'type' => 'doors',
                'count' => $doorCount,
                'position' => 'bottom',
                'height_inches' => null, // Calculated from remaining space
                'needs_rail_below' => false, // Bottom of opening zone
            ];
        }

        return $openings;
    }

    /**
     * Generate component list for cut list
     *
     * @param Cabinet $cabinet
     * @param array $stileConfig
     * @param array $railConfig
     * @param array $config
     * @return array
     */
    protected function generateComponentList(
        Cabinet $cabinet,
        array $stileConfig,
        array $railConfig,
        array $config
    ): array {
        $components = [];

        // Get material info
        $material = $cabinet->cabinetRun?->faceframe?->material ?? 'Hardwood';
        $thickness = $config['thickness'];

        // === STILES ===
        $components[] = [
            'type' => 'stile',
            'instance' => 'left',
            'qty' => 1,
            'width' => $stileConfig['width'],
            'length' => $stileConfig['length'],
            'thickness' => $thickness,
            'material' => $material,
        ];

        $components[] = [
            'type' => 'stile',
            'instance' => 'right',
            'qty' => 1,
            'width' => $stileConfig['width'],
            'length' => $stileConfig['length'],
            'thickness' => $thickness,
            'material' => $material,
        ];

        // Center stiles
        for ($i = 1; $i <= $stileConfig['center_count']; $i++) {
            $components[] = [
                'type' => 'stile',
                'instance' => "center_{$i}",
                'qty' => 1,
                'width' => $stileConfig['width'],
                'length' => $stileConfig['length'],
                'thickness' => $thickness,
                'material' => $material,
            ];
        }

        // === RAILS ===
        if ($railConfig['has_top']) {
            $components[] = [
                'type' => 'rail',
                'instance' => 'top',
                'qty' => 1,
                'width' => $railConfig['width'],
                'length' => $railConfig['length'],
                'thickness' => $thickness,
                'material' => $material,
            ];
        }

        if ($railConfig['has_bottom']) {
            $components[] = [
                'type' => 'rail',
                'instance' => 'bottom',
                'qty' => 1,
                'width' => $railConfig['width'],
                'length' => $railConfig['length'],
                'thickness' => $thickness,
                'material' => $material,
            ];
        }

        // Intermediate rails
        for ($i = 1; $i <= $railConfig['intermediate_count']; $i++) {
            $components[] = [
                'type' => 'rail',
                'instance' => "intermediate_{$i}",
                'qty' => 1,
                'width' => $railConfig['width'],
                'length' => $railConfig['length'],
                'thickness' => $thickness,
                'material' => $material,
            ];
        }

        return $components;
    }

    /**
     * Get rules explanation for a cabinet (for debugging/display)
     *
     * @param Cabinet $cabinet
     * @return array Human-readable explanation of rules applied
     */
    public function explainRules(Cabinet $cabinet): array
    {
        $explanation = [];

        $cabinetType = $cabinet->cabinet_type ?? 'base';
        $drawerCount = $cabinet->drawer_count ?? 0;
        $doorCount = $cabinet->door_count ?? 0;

        // Face frame rule
        if ($this->hasFaceFrame($cabinet)) {
            $explanation[] = "Cabinet type '{$cabinetType}' uses FACE FRAME construction";
        } else {
            $explanation[] = "Cabinet type '{$cabinetType}' is FRAMELESS (no face frame)";
            return $explanation;
        }

        // Stiles
        $explanation[] = "STILES: Always 2 (left + right)";
        if ($doorCount >= 2) {
            $centerStiles = (int) floor($doorCount / 2);
            $explanation[] = "  + {$centerStiles} center stile(s) for {$doorCount} doors";
        }

        // Bottom rail
        if ($this->hasBottomRail($cabinet)) {
            $explanation[] = "BOTTOM RAIL: YES (explicitly required for this cabinet type)";
        } else {
            $explanation[] = "BOTTOM RAIL: NO (TCS standard - cabinet bottom panel serves this function)";
        }

        // Intermediate rails
        if ($drawerCount > 1) {
            $rails = $drawerCount - 1;
            $explanation[] = "INTERMEDIATE RAILS: {$rails} between {$drawerCount} drawers";
        }

        if ($drawerCount > 0 && $doorCount > 0) {
            $explanation[] = "INTERMEDIATE RAILS: +1 between drawer section and door section";
        }

        return $explanation;
    }
}
