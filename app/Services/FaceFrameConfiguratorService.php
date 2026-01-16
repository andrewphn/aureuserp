<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Faceframe;
use Webkul\Project\Models\ConstructionTemplate;
use Webkul\Product\Models\Product;

/**
 * Face Frame Configurator Service
 *
 * Auto-generates face frame configuration based on cabinet type.
 * Handles inheritance: ConstructionTemplate → CabinetRun Faceframe → Cabinet
 *
 * TCS Standards (Levi/Bryan, Jan 2025):
 * - Face frames are 5/4 hardwood (thickness from material)
 * - Stile width: 1.5" standard, 1.75" wide
 * - Rail width: 1.5"
 * - Door gap: 1/8"
 * - Sink bases: NO bottom rail
 * - Normal bases: YES bottom rail
 *
 * NOTE: This service delegates rail/stile logic to RailsAndStilesConfiguratorService
 * for centralized rule management.
 */
class FaceFrameConfiguratorService
{
    protected RailsAndStilesConfiguratorService $railsAndStiles;

    public function __construct(?RailsAndStilesConfiguratorService $railsAndStiles = null)
    {
        $this->railsAndStiles = $railsAndStiles ?? new RailsAndStilesConfiguratorService();
    }

    /**
     * Cabinet types that should NOT have a bottom rail
     * @deprecated Use RailsAndStilesConfiguratorService::NO_BOTTOM_RAIL_TYPES
     */
    public const NO_BOTTOM_RAIL_TYPES = [
        'sink_base',
        'base_sink',
        'vanity_sink',
        'kitchen_sink',
        'appliance_opening',
    ];

    /**
     * Cabinet types that are typically frameless (no face frame)
     * @deprecated Use RailsAndStilesConfiguratorService::FRAMELESS_TYPES
     */
    public const FRAMELESS_TYPES = [
        'closet_unit',
        'wardrobe',
        'euro_base',
        'frameless',
    ];

    /**
     * Get the effective face frame thickness from material product
     *
     * @param Product|null $materialProduct The face frame material product
     * @param float $fallback Fallback thickness if not found
     * @return float Thickness in inches
     */
    public function getThicknessFromMaterial(?Product $materialProduct, float $fallback = 0.75): float
    {
        if (!$materialProduct) {
            return $fallback;
        }

        // Try to get thickness from product attributes
        // Common attribute names for thickness
        $thicknessNames = ['thickness', 'Thickness', 'material_thickness', 'actual_thickness'];

        foreach ($thicknessNames as $name) {
            $thickness = $materialProduct->getSpecValue($name);
            if ($thickness !== null && $thickness > 0) {
                return $thickness;
            }
        }

        // Try attribute_values directly
        $thicknessAttr = $materialProduct->attribute_values()
            ->whereHas('attribute', function ($query) {
                $query->where('name', 'like', '%thickness%')
                    ->orWhere('code', 'like', '%thickness%');
            })
            ->first();

        if ($thicknessAttr) {
            $value = $thicknessAttr->numeric_value ?? null;
            if ($value !== null && $value > 0) {
                return (float) $value;
            }
        }

        return $fallback;
    }

    /**
     * Get effective face frame configuration for a cabinet
     *
     * Inheritance chain: ConstructionTemplate → CabinetRun Faceframe → Cabinet
     *
     * @param Cabinet $cabinet
     * @return array Face frame configuration
     */
    public function getEffectiveConfig(Cabinet $cabinet): array
    {
        // 1. Start with construction template defaults
        $template = $cabinet->constructionTemplate
            ?? $cabinet->room?->constructionTemplate
            ?? $cabinet->room?->project?->constructionTemplate
            ?? ConstructionTemplate::where('is_default', true)->first();

        // 2. Get cabinet run faceframe (if exists)
        $runFaceframe = $cabinet->cabinetRun?->faceframe;

        // 3. Get material product (cabinet → run → template)
        $materialProduct = $cabinet->faceFrameMaterialProduct
            ?? $runFaceframe?->materialProduct
            ?? $template?->defaultFaceFrameMaterialProduct;

        // 4. Get thickness from material (not hardcoded!)
        $thickness = $this->getThicknessFromMaterial(
            $materialProduct,
            $template?->face_frame_thickness ?? 0.75
        );

        // 5. Build effective config with inheritance
        return [
            // Source tracking (for debugging)
            'source' => [
                'template_id' => $template?->id,
                'run_faceframe_id' => $runFaceframe?->id,
                'material_product_id' => $materialProduct?->id,
            ],

            // Material (from product, not hardcoded string)
            'material_product_id' => $materialProduct?->id,
            'material_name' => $materialProduct?->name ?? 'Hardwood',
            'thickness' => $thickness,

            // Dimensions (cabinet → run → template)
            'stile_width' => $cabinet->face_frame_stile_width_inches
                ?? $runFaceframe?->stile_width
                ?? $template?->face_frame_stile_width
                ?? Faceframe::STANDARD_STILE_WIDTH,

            'rail_width' => $cabinet->face_frame_rail_width_inches
                ?? $runFaceframe?->rail_width
                ?? $template?->face_frame_rail_width
                ?? Faceframe::STANDARD_RAIL_WIDTH,

            'door_gap' => $cabinet->face_frame_door_gap_inches
                ?? $runFaceframe?->door_gap
                ?? $template?->face_frame_door_gap
                ?? Faceframe::STANDARD_DOOR_GAP,

            // Style options (run → defaults)
            'joinery_type' => $runFaceframe?->joinery_type ?? 'pocket_hole',
            'overlay_type' => $runFaceframe?->overlay_type ?? 'full_overlay',
            'beaded' => $runFaceframe?->beaded_face_frame ?? false,
        ];
    }

    /**
     * Check if cabinet type uses face frame construction
     * Delegates to RailsAndStilesConfiguratorService
     *
     * @param Cabinet $cabinet
     * @return bool
     */
    public function hasFaceFrame(Cabinet $cabinet): bool
    {
        return $this->railsAndStiles->hasFaceFrame($cabinet);
    }

    /**
     * Check if cabinet type should have a bottom rail
     * Delegates to RailsAndStilesConfiguratorService
     *
     * @param Cabinet $cabinet
     * @return bool
     */
    public function hasBottomRail(Cabinet $cabinet): bool
    {
        return $this->railsAndStiles->hasBottomRail($cabinet);
    }

    /**
     * Check if cabinet type should have a top rail
     * Delegates to RailsAndStilesConfiguratorService
     *
     * @param Cabinet $cabinet
     * @return bool
     */
    public function hasTopRail(Cabinet $cabinet): bool
    {
        return $this->railsAndStiles->hasTopRail($cabinet);
    }

    /**
     * Get number of stiles (left + right + center stiles)
     * Uses RailsAndStilesConfiguratorService for calculation
     *
     * @param Cabinet $cabinet
     * @return int
     */
    public function getStileCount(Cabinet $cabinet): int
    {
        $config = $this->railsAndStiles->generateConfiguration($cabinet);
        return $config['stiles']['count'] ?? 2;
    }

    /**
     * Get number of intermediate rails (drawer rails, dividers)
     * Delegates to RailsAndStilesConfiguratorService
     *
     * @param Cabinet $cabinet
     * @return int
     */
    public function getIntermediateRailCount(Cabinet $cabinet): int
    {
        return $this->railsAndStiles->calculateIntermediateRails($cabinet);
    }

    /**
     * Generate complete face frame component list for a cabinet
     * Delegates component generation to RailsAndStilesConfiguratorService
     * and merges with material information from this service
     *
     * @param Cabinet $cabinet
     * @return array Face frame components with dimensions
     */
    public function generateComponents(Cabinet $cabinet): array
    {
        // Get rails and stiles configuration
        $railsAndStilesConfig = $this->railsAndStiles->generateConfiguration($cabinet);

        if (!$railsAndStilesConfig['has_face_frame']) {
            return [
                'has_face_frame' => false,
                'construction_style' => 'frameless',
                'components' => [],
            ];
        }

        // Get material configuration from this service (handles material product lookup)
        $effectiveConfig = $this->getEffectiveConfig($cabinet);

        // Update components with material info (RailsAndStiles uses generic material)
        $components = $railsAndStilesConfig['components'];
        foreach ($components as &$component) {
            $component['material'] = $effectiveConfig['material_name'];
            $component['thickness'] = $effectiveConfig['thickness'];
        }

        return [
            'has_face_frame' => true,
            'construction_style' => 'face_frame',
            'cabinet_type' => $cabinet->cabinet_type,
            'config' => $effectiveConfig,
            'summary' => $railsAndStilesConfig['summary'],
            'dimensions' => [
                'stile_width' => $railsAndStilesConfig['stiles']['width'],
                'stile_length' => $railsAndStilesConfig['stiles']['length'],
                'rail_width' => $railsAndStilesConfig['rails']['width'],
                'rail_length' => $railsAndStilesConfig['rails']['length'],
                'thickness' => $effectiveConfig['thickness'],
            ],
            'components' => $components,
            'rules_applied' => $this->railsAndStiles->explainRules($cabinet),
        ];
    }

    /**
     * Calculate face frame opening dimensions
     *
     * @param Cabinet $cabinet
     * @return array Opening dimensions
     */
    public function calculateOpenings(Cabinet $cabinet): array
    {
        $config = $this->getEffectiveConfig($cabinet);
        $faceFrameData = $this->generateComponents($cabinet);

        if (!$faceFrameData['has_face_frame']) {
            return ['has_face_frame' => false];
        }

        $cabinetWidth = $cabinet->length_inches ?? $cabinet->width_inches ?? 24;
        $toeKickHeight = $cabinet->toe_kick_height_inches ?? 4.0;
        $boxHeight = ($cabinet->height_inches ?? 30) - $toeKickHeight;

        $stileWidth = $config['stile_width'];
        $railWidth = $config['rail_width'];
        $doorGap = $config['door_gap'];

        $stileCount = $faceFrameData['summary']['stile_count'];
        $railCount = $faceFrameData['summary']['rail_count'];

        // Opening width (total width minus stiles)
        $totalStileWidth = $stileCount * $stileWidth;
        $openingWidth = $cabinetWidth - $totalStileWidth;

        // If multiple openings (center stiles), divide
        $openingCount = $stileCount - 1;
        $singleOpeningWidth = $openingWidth / max(1, $openingCount);

        // Opening height (total height minus rails)
        $totalRailHeight = $railCount * $railWidth;
        $openingHeight = $boxHeight - $totalRailHeight;

        // Door dimensions (with gap)
        $doorWidth = $singleOpeningWidth - (2 * $doorGap);
        $doorHeight = $openingHeight - (2 * $doorGap);

        return [
            'has_face_frame' => true,
            'cabinet_width' => $cabinetWidth,
            'box_height' => $boxHeight,
            'stile_count' => $stileCount,
            'rail_count' => $railCount,
            'opening_count' => $openingCount,
            'total_opening_width' => $openingWidth,
            'single_opening_width' => $singleOpeningWidth,
            'opening_height' => $openingHeight,
            'door_gap' => $doorGap,
            'door_width' => $doorWidth,
            'door_height' => $doorHeight,
        ];
    }
}
