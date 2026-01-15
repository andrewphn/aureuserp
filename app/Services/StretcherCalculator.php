<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Stretcher;
use Illuminate\Support\Collection;

/**
 * StretcherCalculator Service
 *
 * Calculates stretcher dimensions and creates stretchers for cabinets.
 *
 * Stretchers are horizontal rails at the top of base cabinets that:
 * - Hold the cabinet square and stable
 * - Provide a surface to attach the countertop
 * - Give drawer slides something to mount to
 *
 * Rule: Number of stretchers = 2 (front + back) + drawer_count (one per drawer)
 *
 * Reference: Master Plan (iridescent-wishing-turing.md) lines 1540-1618
 */
class StretcherCalculator
{
    /**
     * Standard dimensions (in inches)
     */
    public const STANDARD_DEPTH_INCHES = 3.5;       // 3-1/2"
    public const STANDARD_THICKNESS_INCHES = 0.75;  // 3/4"
    public const MINIMUM_DEPTH_INCHES = 3.0;        // 3"
    public const MAXIMUM_DEPTH_INCHES = 4.0;        // 4"
    public const DEFAULT_SIDE_PANEL_THICKNESS = 0.75; // 3/4"

    /**
     * Cabinet types that require stretchers
     */
    protected const STRETCHER_CABINET_TYPES = [
        'base',
        'sink_base',
        'drawer_base',
        'blind_base_corner',
        'lazy_susan',
        'vanity',
        'vanity_drawer',
        'vanity_sink',
        'island',
        'tall',
        'tall_pantry',
    ];

    /**
     * Cabinet types that never need stretchers
     */
    protected const NO_STRETCHER_TYPES = [
        'wall',
        'wall_diagonal_corner',
        'wall_blind_corner',
    ];

    /**
     * Calculate stretcher specifications for a cabinet
     *
     * @param Cabinet $cabinet The cabinet to calculate stretchers for
     * @return array Array of stretcher data to be created
     */
    public function calculateStretchers(Cabinet $cabinet): array
    {
        $stretchers = [];

        if (!$this->cabinetNeedsStretchers($cabinet)) {
            return [];
        }

        // Calculate inside width (cabinet width minus side panels)
        $stretcherWidth = $this->calculateStretcherWidth($cabinet);

        // Front stretcher
        $stretchers[] = $this->buildStretcherData(
            position: Stretcher::POSITION_FRONT,
            stretcherNumber: 1,
            width: $stretcherWidth,
            depth: self::STANDARD_DEPTH_INCHES,
            thickness: self::STANDARD_THICKNESS_INCHES,
            positionFromFront: 0,
            positionFromTop: 0,
            material: $cabinet->box_material ?? 'plywood',
        );

        // Back stretcher
        $stretchers[] = $this->buildStretcherData(
            position: Stretcher::POSITION_BACK,
            stretcherNumber: 2,
            width: $stretcherWidth,
            depth: self::STANDARD_DEPTH_INCHES,
            thickness: self::STANDARD_THICKNESS_INCHES,
            positionFromFront: ($cabinet->depth_inches ?? 24) - self::STANDARD_DEPTH_INCHES,
            positionFromTop: 0,
            material: $cabinet->box_material ?? 'plywood',
        );

        // Drawer support stretchers (one per drawer)
        $drawers = $cabinet->sections()
            ->with('drawers')
            ->get()
            ->flatMap(fn($section) => $section->drawers);

        // Also check direct drawer relationship if cabinet has it
        if ($cabinet->relationLoaded('drawers') || method_exists($cabinet, 'drawers')) {
            try {
                $directDrawers = $cabinet->drawers ?? collect();
                $drawers = $drawers->merge($directDrawers)->unique('id');
            } catch (\Exception $e) {
                // Relationship doesn't exist, continue with section drawers
            }
        }

        $stretcherNumber = 3;
        foreach ($drawers as $drawer) {
            $stretchers[] = $this->buildStretcherData(
                position: Stretcher::POSITION_DRAWER_SUPPORT,
                stretcherNumber: $stretcherNumber,
                width: $stretcherWidth,
                depth: self::STANDARD_DEPTH_INCHES,
                thickness: self::STANDARD_THICKNESS_INCHES,
                positionFromFront: null, // Calculated based on drawer position
                positionFromTop: 0,
                material: $cabinet->box_material ?? 'plywood',
                drawerId: $drawer->id,
            );
            $stretcherNumber++;
        }

        return $stretchers;
    }

    /**
     * Create stretchers for a cabinet based on calculated specs
     *
     * @param Cabinet $cabinet The cabinet to create stretchers for
     * @param bool $force If true, delete existing stretchers first
     * @return Collection Collection of created Stretcher models
     */
    public function createStretchersForCabinet(Cabinet $cabinet, bool $force = false): Collection
    {
        // Delete existing stretchers if force is true
        if ($force) {
            $cabinet->stretchers()->delete();
        }

        // Check if stretchers already exist
        if (!$force && $cabinet->stretchers()->count() > 0) {
            return $cabinet->stretchers;
        }

        $stretcherData = $this->calculateStretchers($cabinet);

        $createdStretchers = collect();

        foreach ($stretcherData as $data) {
            $data['cabinet_id'] = $cabinet->id;
            $stretcher = Stretcher::create($data);
            $createdStretchers->push($stretcher);
        }

        return $createdStretchers;
    }

    /**
     * Determine if a cabinet needs stretchers
     *
     * @param Cabinet $cabinet The cabinet to check
     * @return bool
     */
    public function cabinetNeedsStretchers(Cabinet $cabinet): bool
    {
        // Get cabinet type from product or attributes
        $cabinetType = $cabinet->cabinet_type
            ?? $cabinet->productVariant?->category_name
            ?? $cabinet->product?->category_name
            ?? 'base';

        $cabinetType = strtolower($cabinetType);

        // Wall cabinets don't need stretchers
        if (in_array($cabinetType, self::NO_STRETCHER_TYPES)) {
            return false;
        }

        // Check construction style if available
        $constructionStyle = $cabinet->construction_style ?? 'face_frame';
        $topConstruction = $cabinet->top_construction ?? 'stretchers';

        // Frameless cabinets with full top don't need stretchers
        if ($constructionStyle === 'frameless' && $topConstruction === 'full_top') {
            return false;
        }

        // Face frame cabinets typically use stretchers
        if ($constructionStyle === 'face_frame') {
            return true;
        }

        // Base/tall cabinets with drawers need stretchers for slide mounting
        $drawerCount = $cabinet->drawer_count ?? 0;
        if ($drawerCount > 0) {
            return true;
        }

        // Check if cabinet type is in the stretcher list
        return in_array($cabinetType, self::STRETCHER_CABINET_TYPES);
    }

    /**
     * Calculate the width of stretchers (inside width of cabinet)
     *
     * @param Cabinet $cabinet The cabinet to calculate for
     * @return float The stretcher width in inches
     */
    public function calculateStretcherWidth(Cabinet $cabinet): float
    {
        $cabinetWidth = $cabinet->length_inches ?? $cabinet->width_inches ?? 24;
        $sidePanelThickness = $cabinet->side_panel_thickness ?? self::DEFAULT_SIDE_PANEL_THICKNESS;

        // Stretcher width = cabinet width - (2 × side panel thickness)
        return $cabinetWidth - (2 * $sidePanelThickness);
    }

    /**
     * Calculate cut dimensions with shop rounding (1/16" precision)
     *
     * @param float $width The exact width
     * @param float $depth The exact depth
     * @return array Cut dimensions with shop rounding
     */
    public function calculateCutDimensions(float $width, float $depth): array
    {
        return [
            'cut_width_inches' => $width,
            'cut_width_shop_inches' => $this->roundToSixteenth($width),
            'cut_depth_inches' => $depth,
            'cut_depth_shop_inches' => $this->roundToSixteenth($depth),
        ];
    }

    /**
     * Round a dimension to the nearest 1/16 inch (shop standard)
     *
     * @param float $inches The dimension in inches
     * @return float Rounded to nearest 1/16"
     */
    public function roundToSixteenth(float $inches): float
    {
        return round($inches * 16) / 16;
    }

    /**
     * Get the required stretcher count for a cabinet
     *
     * @param Cabinet $cabinet The cabinet to check
     * @return int Number of stretchers needed (0 if none needed)
     */
    public function getRequiredStretcherCount(Cabinet $cabinet): int
    {
        if (!$this->cabinetNeedsStretchers($cabinet)) {
            return 0;
        }

        // 2 structural (front + back) + drawer count
        $drawerCount = $cabinet->drawer_count ?? 0;

        // Count drawers from sections if drawer_count not set
        if ($drawerCount === 0) {
            $drawerCount = $cabinet->sections()
                ->with('drawers')
                ->get()
                ->flatMap(fn($section) => $section->drawers)
                ->count();
        }

        return 2 + $drawerCount;
    }

    /**
     * Validate existing stretchers against requirements
     *
     * @param Cabinet $cabinet The cabinet to validate
     * @return array Validation result with issues and suggestions
     */
    public function validateStretchers(Cabinet $cabinet): array
    {
        $required = $this->getRequiredStretcherCount($cabinet);
        $existing = $cabinet->stretchers()->count();

        $issues = [];
        $suggestions = [];

        if ($required === 0 && $existing > 0) {
            $issues[] = "Cabinet has {$existing} stretchers but doesn't require any";
            $suggestions[] = [
                'action' => 'remove_stretchers',
                'count' => $existing,
            ];
        } elseif ($existing < $required) {
            $issues[] = "Cabinet has {$existing} stretchers but requires {$required}";
            $suggestions[] = [
                'action' => 'create_stretchers',
                'count' => $required - $existing,
            ];
        }

        // Validate dimensions
        $stretcherWidth = $this->calculateStretcherWidth($cabinet);
        foreach ($cabinet->stretchers as $stretcher) {
            if (abs($stretcher->width_inches - $stretcherWidth) > 0.0625) { // > 1/16" difference
                $issues[] = "Stretcher #{$stretcher->stretcher_number} has incorrect width: {$stretcher->width_inches}\" (should be {$stretcherWidth}\")";
            }
        }

        return [
            'valid' => empty($issues),
            'required_count' => $required,
            'existing_count' => $existing,
            'issues' => $issues,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Generate cut list for all stretchers in a cabinet
     *
     * @param Cabinet $cabinet The cabinet
     * @return array Cut list data for shop
     */
    public function generateCutList(Cabinet $cabinet): array
    {
        $cutList = [];

        foreach ($cabinet->stretchers as $stretcher) {
            $cutList[] = $stretcher->cut_list_data;
        }

        return $cutList;
    }

    /**
     * Build stretcher data array
     */
    protected function buildStretcherData(
        string $position,
        int $stretcherNumber,
        float $width,
        float $depth,
        float $thickness,
        ?float $positionFromFront,
        ?float $positionFromTop,
        string $material,
        ?int $drawerId = null,
    ): array {
        $cutDimensions = $this->calculateCutDimensions($width, $depth);

        return array_merge([
            'position' => $position,
            'stretcher_number' => $stretcherNumber,
            'width_inches' => $width,
            'depth_inches' => $depth,
            'thickness_inches' => $thickness,
            'position_from_front_inches' => $positionFromFront,
            'position_from_top_inches' => $positionFromTop,
            'material' => $material,
            'supports_drawer' => $position === Stretcher::POSITION_DRAWER_SUPPORT,
            'drawer_id' => $drawerId,
        ], $cutDimensions);
    }
}
