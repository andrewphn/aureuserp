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
 * TCS Standard (Bryan Patton, Jan 2025): "3 inch stretchers"
 * Reference: Master Plan (iridescent-wishing-turing.md) lines 1540-1618
 *
 * Construction standards are now configurable via ConstructionTemplate.
 * The ConstructionStandardsService resolves the effective template with inheritance:
 * Cabinet -> Room -> Project -> Global Default -> Fallback
 */
class StretcherCalculator
{
    /**
     * Construction standards service for template resolution.
     */
    protected ?ConstructionStandardsService $standards = null;

    /**
     * Create a new StretcherCalculator instance.
     */
    public function __construct(?ConstructionStandardsService $standards = null)
    {
        $this->standards = $standards ?? app(ConstructionStandardsService::class);
    }

    /**
     * Standard dimensions (in inches) - kept as fallback constants
     *
     * TCS Standard: 3" stretcher height (Bryan Patton, Jan 2025)
     * Note: "depth" here refers to front-to-back dimension of stretcher
     */
    public const STANDARD_DEPTH_INCHES = 3.0;       // 3" TCS standard (was 3.5")
    public const STANDARD_THICKNESS_INCHES = 0.75;  // 3/4"
    public const MINIMUM_DEPTH_INCHES = 2.5;        // 2-1/2"
    public const MAXIMUM_DEPTH_INCHES = 4.0;        // 4"
    public const DEFAULT_SIDE_PANEL_THICKNESS = 0.75; // 3/4"

    /**
     * Gap constants for drawer face layout
     *
     * TCS Standard (Bryan Patton, Jan 2025):
     * - Gap between drawer faces: 1/8" (0.125")
     * - Stretcher splits the gap between drawer faces
     * - Bottom drawer face lines up with bottom of cabinet (above toe kick)
     */
    public const STANDARD_GAP_INCHES = 0.125;  // 1/8" gap between drawer faces
    public const STANDARD_REVEAL_INCHES = 0.125;  // 1/8" reveal at top/bottom

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

        // Get stretcher depth from cabinet or use default (TCS: 3")
        $stretcherDepth = $this->getStretcherDepth($cabinet);

        // Front stretcher
        $stretchers[] = $this->buildStretcherData(
            position: Stretcher::POSITION_FRONT,
            stretcherNumber: 1,
            width: $stretcherWidth,
            depth: $stretcherDepth,
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
            depth: $stretcherDepth,
            thickness: self::STANDARD_THICKNESS_INCHES,
            positionFromFront: ($cabinet->depth_inches ?? 24) - $stretcherDepth,
            positionFromTop: 0,
            material: $cabinet->box_material ?? 'plywood',
        );

        // Drawer support stretchers (one per drawer, except the bottom-most)
        // Each stretcher supports the drawer ABOVE it (slides mount on top of stretcher)
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

        // Get false fronts too (they take up space but don't need stretchers under them)
        $falseFronts = collect();
        if (method_exists($cabinet, 'falseFronts')) {
            try {
                $falseFronts = $cabinet->falseFronts ?? collect();
            } catch (\Exception $e) {
                // No false fronts relationship
            }
        }

        // Sort all components by position (top to bottom)
        // drawer_position: 'upper', 'middle', 'lower' or use sort_order/drawer_number
        $allComponents = $drawers->merge($falseFronts)
            ->sortBy(function ($component) {
                // Priority: explicit sort_order > drawer_number > position string
                if (isset($component->sort_order)) {
                    return $component->sort_order;
                }
                if (isset($component->drawer_number)) {
                    return $component->drawer_number;
                }
                // Fallback to position string mapping
                $positionMap = ['upper' => 1, 'middle' => 2, 'lower' => 3, 'bottom' => 4];
                return $positionMap[$component->drawer_position ?? 'lower'] ?? 5;
            })
            ->values();

        // Calculate stretcher positions
        // Stretchers go BETWEEN drawers (not under the bottom drawer - it mounts on cabinet bottom)
        $stretcherNumber = 3;
        $componentsAbove = [];

        foreach ($allComponents as $index => $component) {
            $componentsAbove[] = $component;

            // Only create stretcher if there's a drawer below this one
            // (The bottom drawer's slides mount on the cabinet bottom, not a stretcher)
            $isLastComponent = ($index === $allComponents->count() - 1);

            if (!$isLastComponent && $this->componentIsDrawer($component)) {
                // Calculate position from top: sum of faces above + gaps
                $positionFromTop = $this->calculateStretcherPositionFromTop(
                    $cabinet,
                    $componentsAbove,
                    $component->stretcher_position_override_inches ?? null
                );

                $stretchers[] = $this->buildStretcherData(
                    position: Stretcher::POSITION_DRAWER_SUPPORT,
                    stretcherNumber: $stretcherNumber,
                    width: $stretcherWidth,
                    depth: $stretcherDepth,
                    thickness: self::STANDARD_THICKNESS_INCHES,
                    positionFromFront: $this->calculateStretcherDepthOffset($cabinet, $stretcherDepth),
                    positionFromTop: $positionFromTop,
                    material: $cabinet->box_material ?? 'plywood',
                    drawerId: $component->id ?? null,
                );
                $stretcherNumber++;
            }
        }

        return $stretchers;
    }

    /**
     * Check if a component is a drawer (vs false front, door, etc.)
     *
     * @param mixed $component The component to check
     * @return bool True if it's a drawer
     */
    protected function componentIsDrawer($component): bool
    {
        // Check class name
        $className = get_class($component);
        if (str_contains($className, 'Drawer')) {
            return true;
        }

        // Check for drawer-specific attributes
        if (isset($component->drawer_number) || isset($component->box_width_inches)) {
            return true;
        }

        // Check type attribute
        $type = $component->type ?? $component->component_type ?? null;
        if ($type === 'drawer') {
            return true;
        }

        return false;
    }

    /**
     * Calculate stretcher depth offset from front.
     *
     * TCS Rule: Stretcher set back from front so drawer box doesn't hit it.
     *
     * @param Cabinet $cabinet The cabinet
     * @param float $stretcherDepth The stretcher depth
     * @return float Position from front in inches
     */
    protected function calculateStretcherDepthOffset(Cabinet $cabinet, float $stretcherDepth): float
    {
        // Default offset: 0.5" from front edge
        // This can be adjusted based on slide hardware requirements
        $offset = 0.5;

        return $offset;
    }

    /**
     * Get stretcher depth (front-to-back) from cabinet or use default
     *
     * Priority: Cabinet override → Template → Constant fallback
     *
     * Uses cabinet's configurable stretcher_height_inches if set,
     * otherwise uses construction template, then falls back to TCS standard (3").
     *
     * @param Cabinet $cabinet The cabinet to get depth for
     * @return float The stretcher depth in inches
     */
    public function getStretcherDepth(Cabinet $cabinet): float
    {
        // Use cabinet's configured stretcher height if set
        if (!empty($cabinet->stretcher_height_inches)) {
            return (float) $cabinet->stretcher_height_inches;
        }

        // Get from construction template (inherits from room/project/default)
        return $this->standards->getStretcherDepth($cabinet);
    }

    /**
     * Get stretcher thickness from cabinet or template
     *
     * @param Cabinet $cabinet The cabinet to get thickness for
     * @return float The stretcher thickness in inches
     */
    public function getStretcherThickness(Cabinet $cabinet): float
    {
        return $this->standards->getStretcherThickness($cabinet);
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
     * TCS Standard (Bryan Patton, Jan 2025):
     * - Base cabinets use 3" stretchers (no full top)
     * - Wall cabinets have full tops
     * - Sink cabinets have stretchers with extended sides (3/4" extra)
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

        // Check explicit top_construction_type field first (new field from Bryan's specs)
        $topConstructionType = $cabinet->top_construction_type ?? null;
        if ($topConstructionType === 'full_top' || $topConstructionType === 'none') {
            return false;
        }
        if ($topConstructionType === 'stretchers') {
            return true;
        }

        // Fall back to construction_style check for legacy cabinets
        $constructionStyle = $cabinet->construction_style ?? $cabinet->construction_type ?? 'face_frame';
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
     * Uses side panel thickness from construction template.
     *
     * @param Cabinet $cabinet The cabinet to calculate for
     * @return float The stretcher width in inches
     */
    public function calculateStretcherWidth(Cabinet $cabinet): float
    {
        $cabinetWidth = $cabinet->length_inches ?? $cabinet->width_inches ?? 24;
        $sidePanelThickness = $cabinet->side_panel_thickness ?? $this->standards->getSidePanelThickness($cabinet);

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
     * Calculate stretcher vertical position from top of box.
     *
     * TCS Rule (Bryan Patton, Jan 2025):
     * - "The stretcher is centered on the drawer faces"
     * - "The stretcher splits the gap between drawer faces"
     * - Bottom drawer face lines up with bottom of cabinet (no bottom overlap)
     *
     * Position is calculated as the sum of all drawer faces ABOVE this stretcher,
     * plus the gap between faces (stretcher centered in gap).
     *
     * @param Cabinet $cabinet The cabinet
     * @param array $drawersAbove Collection of drawers/components above this stretcher
     * @param float|null $override Manual override position (from CAD)
     * @return float Position from top of box in inches (to TOP of stretcher)
     */
    public function calculateStretcherPositionFromTop(
        Cabinet $cabinet,
        array $drawersAbove = [],
        ?float $override = null
    ): float {
        // If override provided, use it directly
        if ($override !== null) {
            return $override;
        }

        // Get box height (cabinet height - toe kick)
        $boxHeight = $this->getBoxHeight($cabinet);

        // Calculate position from faces above
        $position = 0.0;

        foreach ($drawersAbove as $drawer) {
            // Get the face height (front_height_inches for drawers, height_inches for false fronts)
            $faceHeight = $drawer->front_height_inches
                ?? $drawer->height_inches
                ?? $drawer->opening_height_inches
                ?? 0;

            $position += $faceHeight;
            $position += self::STANDARD_GAP_INCHES; // Add gap after each face
        }

        // The stretcher is centered in the gap, so we're at the top of the stretcher
        // Position already accounts for faces + gaps above

        return $position;
    }

    /**
     * Calculate stretcher position from bottom of box.
     *
     * Alternative calculation method - calculates based on faces BELOW the stretcher.
     *
     * TCS Rule: Bottom drawer face lines up with bottom of cabinet (above toe kick).
     *
     * @param Cabinet $cabinet The cabinet
     * @param array $drawersBelow Collection of drawers below this stretcher
     * @param float|null $override Manual override position
     * @return float Position from bottom of box in inches (to TOP of stretcher)
     */
    public function calculateStretcherPositionFromBottom(
        Cabinet $cabinet,
        array $drawersBelow = [],
        ?float $override = null
    ): float {
        // If override provided, use it
        if ($override !== null) {
            return $override;
        }

        // Start from bottom (no reveal - face lines up with bottom)
        $position = 0.0;

        foreach ($drawersBelow as $drawer) {
            $faceHeight = $drawer->front_height_inches
                ?? $drawer->height_inches
                ?? $drawer->opening_height_inches
                ?? 0;

            $position += $faceHeight;
        }

        // Add half the gap (stretcher centered in gap)
        $position += self::STANDARD_GAP_INCHES / 2;

        return $position;
    }

    /**
     * Get box height (cabinet height minus toe kick).
     *
     * @param Cabinet $cabinet The cabinet
     * @return float Box height in inches
     */
    public function getBoxHeight(Cabinet $cabinet): float
    {
        $cabinetHeight = $cabinet->height_inches ?? 30;
        $toeKickHeight = $cabinet->toe_kick_height_inches ?? 4;

        return $cabinetHeight - $toeKickHeight;
    }

    /**
     * Convert position from top to position from bottom.
     *
     * @param float $positionFromTop Position from top in inches
     * @param Cabinet $cabinet The cabinet (for box height)
     * @return float Position from bottom in inches
     */
    public function convertPositionToFromBottom(float $positionFromTop, Cabinet $cabinet): float
    {
        $boxHeight = $this->getBoxHeight($cabinet);
        return $boxHeight - $positionFromTop;
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
