<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Webkul\Project\Models\Faceframe;

/**
 * Calculates drawer box dimensions based on cabinet opening dimensions
 * and hardware specifications (drawer slides).
 *
 * Uses the EAV product attribute system to fetch slide clearance requirements.
 *
 * Also provides face frame style information that determines rail requirements:
 * - Full Overlay: Rails only needed for gaps >= 3/4" (default TCS style)
 * - Inset: Rails always needed to define each opening
 * - Partial Overlay: Rails always needed to frame each opening
 * - Frameless/European: No face frame at all
 */
class DrawerConfiguratorService
{
    protected DrawerHardwareService $hardwareService;

    public function __construct(DrawerHardwareService $hardwareService)
    {
        $this->hardwareService = $hardwareService;
    }

    /**
     * Blum TANDEM 563H specifications by drawer side thickness.
     * 
     * 5/8" (16mm) drawer sides:
     * - Width deduction: 13/32" (10mm) total
     * - Inside width: Opening - 1-5/16" (33mm)
     * 
     * 1/2" (13mm) drawer sides:
     * - Width deduction: 5/8" (16mm) total
     * - Inside width: Opening - 1-21/32" (42mm)
     * - Bottom recess: 1/2" (13mm) from bottom edge
     * 
     * Both thicknesses:
     * - Height deduction: 13/16" (20mm) total
     * - Top clearance: 1/4" (6mm)
     * - Bottom clearance: 9/16" (14mm)
     */
    public const SIDE_DEDUCTION_5_8 = 0.40625;      // 13/32" for 5/8" sides
    public const SIDE_DEDUCTION_1_2 = 0.625;        // 5/8" for 1/2" sides
    public const INSIDE_WIDTH_DEDUCTION_5_8 = 1.3125;  // 1-5/16" (33mm)
    public const INSIDE_WIDTH_DEDUCTION_1_2 = 1.65625; // 1-21/32" (42mm)
    public const BOTTOM_RECESS_1_2 = 0.5;           // 1/2" (13mm) bottom panel recess
    public const BOTTOM_RECESS_5_8 = 0.5;           // 1/2" (13mm) bottom panel recess
    public const TOP_CLEARANCE = 0.25;              // 1/4" (6mm)
    public const BOTTOM_CLEARANCE = 0.5625;         // 9/16" (14mm)
    public const HEIGHT_DEDUCTION = 0.8125;         // 13/16" (20mm) total

    /**
     * Construction specifications (dovetail box construction).
     * 
     * - Material: 1/2" plywood for sides, front, back
     * - Bottom: 1/4" plywood in 1/4" deep dado
     * - Bottom recess: 1/2" from bottom edge
     * - Joinery: Dovetails (sides extend past front/back)
     * - False front applied separately
     */
    public const MATERIAL_THICKNESS = 0.5;          // 1/2" sides/front/back
    public const BOTTOM_THICKNESS = 0.25;           // 1/4" bottom panel
    public const DADO_DEPTH = 0.25;                 // 1/4" dado for bottom
    public const BOTTOM_DADO_HEIGHT = 0.5;          // 1/2" up from bottom edge
    public const BOTTOM_CLEARANCE_IN_DADO = 0.0625; // 1/16" clearance in dado
    
    /**
     * Shop practice: Add 1/4" to nominal slide length for depth.
     * This provides safety clearance for proper slide operation.
     */
    public const SHOP_DEPTH_ADDITION = 0.25;        // 1/4" added to slide length

    /**
     * Shop practice: Add 3/4" to slide length for minimum cabinet depth.
     * This is simpler than Blum's ~29/32" spec and works reliably in practice.
     *
     * Blum spec vs Shop practice:
     * - 21" slide: Blum 21-15/16" → Shop 21-3/4"
     * - 18" slide: Blum 18-29/32" → Shop 18-3/4"
     * - 15" slide: Blum 15-29/32" → Shop 15-3/4"
     * - 12" slide: Blum 12-29/32" → Shop 12-3/4"
     * - 9" slide: Blum 10-15/32" → Shop 9-3/4"
     */
    public const SHOP_MIN_DEPTH_ADDITION = 0.75;    // 3/4" added to slide length for min cabinet depth

    /**
     * Face Frame Style Constants
     *
     * These determine how the face frame is constructed and when rails are needed:
     *
     * FULL_OVERLAY (TCS default):
     * - Doors/drawer fronts cover most of the face frame
     * - Rails only added when gap >= 3/4" between components
     * - If gap < 3/4", just use reveal gap (no rail needed)
     *
     * INSET:
     * - Doors/drawer fronts fit inside the face frame opening
     * - Rails ALWAYS needed to define each opening precisely
     * - Creates a traditional look with visible frame around each component
     *
     * PARTIAL_OVERLAY (Traditional):
     * - Doors/drawer fronts partially cover the face frame
     * - Rails ALWAYS needed to frame each opening
     * - Reveals more of the face frame than full overlay
     *
     * FRAMELESS (European/32mm):
     * - No face frame at all
     * - Cabinet box is finished, doors mount directly to sides
     * - Full overlay only style
     */
    public const FACE_FRAME_STYLE_FULL_OVERLAY = 'full_overlay';
    public const FACE_FRAME_STYLE_INSET = 'inset';
    public const FACE_FRAME_STYLE_PARTIAL_OVERLAY = 'partial_overlay';
    public const FACE_FRAME_STYLE_FRAMELESS = 'frameless';

    /**
     * Available face frame styles with descriptions.
     */
    public const FACE_FRAME_STYLES = [
        self::FACE_FRAME_STYLE_FULL_OVERLAY => [
            'name' => 'Full Overlay',
            'description' => 'Doors/drawers cover most of the face frame. Rails only added for gaps >= 3/4".',
            'has_face_frame' => true,
            'rails_required' => 'conditional',
            'min_rail_gap' => 0.75, // 3/4"
        ],
        self::FACE_FRAME_STYLE_INSET => [
            'name' => 'Inset',
            'description' => 'Doors/drawers fit inside the face frame opening. Rails always define each opening.',
            'has_face_frame' => true,
            'rails_required' => 'always',
            'min_rail_gap' => 0, // Always add rails
        ],
        self::FACE_FRAME_STYLE_PARTIAL_OVERLAY => [
            'name' => 'Partial Overlay (Traditional)',
            'description' => 'Doors/drawers partially cover the face frame. Rails frame each opening.',
            'has_face_frame' => true,
            'rails_required' => 'always',
            'min_rail_gap' => 0, // Always add rails
        ],
        self::FACE_FRAME_STYLE_FRAMELESS => [
            'name' => 'Frameless (European/32mm)',
            'description' => 'No face frame. Cabinet box is finished, doors mount directly to sides.',
            'has_face_frame' => false,
            'rails_required' => 'none',
            'min_rail_gap' => null,
        ],
    ];

    /**
     * Blum official minimum inside cabinet depths (from spec sheet).
     * Key: slide length in inches, Value: minimum depth in inches.
     */
    public const BLUM_MIN_CABINET_DEPTHS = [
        21 => 21.9375,    // 21-15/16" (557mm)
        18 => 18.90625,   // 18-29/32" (480mm)
        15 => 15.90625,   // 15-29/32" (404mm)
        12 => 12.90625,   // 12-29/32" (328mm)
        9  => 10.46875,   // 10-15/32" (266mm)
    ];

    /**
     * Calculate drawer box dimensions from cabinet opening dimensions.
     * 
     * Per Blum TANDEM 563H specifications:
     * - Box width = opening width - side deduction (varies by drawer side thickness)
     * - Box height = opening height - top clearance - bottom clearance  
     * - Box depth = slide length (drawer length equals nominal slide size)
     * 
     * Side deductions per Blum:
     * - 5/8" drawer sides: 13/32" (10mm) total
     * - 1/2" drawer sides: 5/8" (16mm) total
     * 
     * Note: The runner extends BEYOND the drawer box (not the other way around).
     * 
     * @param float $openingWidth Cabinet opening width in inches
     * @param float $openingHeight Cabinet opening height in inches
     * @param float $openingDepth Cabinet opening depth in inches
     * @param float $drawerSideThickness Drawer side material thickness (0.5 or 0.625 inches)
     * @return array Calculated drawer dimensions and selected hardware
     */
    public function calculateDrawerDimensions(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        float $drawerSideThickness = 0.5  // Default to 1/2" sides (most common)
    ): array {
        // Get the appropriate slide for this depth
        $slide = $this->hardwareService->getSlideProductForDepth($openingDepth);
        
        if (!$slide) {
            return $this->getDefaultCalculation($openingWidth, $openingHeight, $openingDepth, $drawerSideThickness);
        }

        $specs = $this->hardwareService->getSlideSpecs($slide);

        // Determine specs based on drawer side thickness (per Blum spec)
        $is5_8Sides = $drawerSideThickness >= 0.625;
        
        $sideDeduction = $is5_8Sides ? self::SIDE_DEDUCTION_5_8 : self::SIDE_DEDUCTION_1_2;
        $insideWidthDeduction = $is5_8Sides ? self::INSIDE_WIDTH_DEDUCTION_5_8 : self::INSIDE_WIDTH_DEDUCTION_1_2;
        $bottomRecess = $is5_8Sides ? self::BOTTOM_RECESS_5_8 : self::BOTTOM_RECESS_1_2;
        
        $topGap = self::TOP_CLEARANCE;
        $bottomGap = self::BOTTOM_CLEARANCE;
        $slideLength = $specs['length'] ?? $openingDepth - 1;

        // Calculate drawer box dimensions
        $boxOutsideWidth = $openingWidth - $sideDeduction;
        $boxInsideWidth = $openingWidth - $insideWidthDeduction;
        $boxHeight = $openingHeight - self::HEIGHT_DEDUCTION;
        // Drawer depth = slide length (per Blum spec, drawer matches nominal slide size)
        $boxDepth = $slideLength;
        
        // Shop height: rounded DOWN to nearest 1/2" for safety
        $boxHeightShop = self::roundDownToHalfInch($boxHeight);
        
        // Shop depth: slide length + 1/4" for safety clearance
        $boxDepthShop = $boxDepth + self::SHOP_DEPTH_ADDITION;

        return [
            'opening' => [
                'width' => $openingWidth,
                'height' => $openingHeight,
                'depth' => $openingDepth,
            ],
            'drawer_box' => [
                'outside_width' => round($boxOutsideWidth, 4),
                'inside_width' => round($boxInsideWidth, 4),
                'height' => round($boxHeight, 4),
                'height_shop' => $boxHeightShop,  // Rounded down for shop use
                'depth' => round($boxDepth, 4),
                'depth_shop' => round($boxDepthShop, 4),  // +1/4" for shop use
            ],
            'drawer_side_thickness' => $drawerSideThickness,
            'bottom_panel' => [
                'recess_from_bottom' => $bottomRecess,
                'width' => round($boxInsideWidth, 4),
                'depth' => round($boxDepth - $bottomRecess, 4),
            ],
            'clearances' => [
                'side_deduction' => $sideDeduction,
                'inside_width_deduction' => $insideWidthDeduction,
                'top' => $topGap,
                'bottom' => $bottomGap,
                'height_deduction' => self::HEIGHT_DEDUCTION,
            ],
            'hardware' => [
                'slide_product_id' => $slide->id,
                'slide_name' => $slide->name,
                'slide_length' => $slideLength,
                'weight_capacity' => $specs['weight_capacity'],
                'min_cabinet_depth' => $specs['min_cabinet_depth'],
                'min_cabinet_depth_blum' => self::BLUM_MIN_CABINET_DEPTHS[(int)$slideLength] ?? ($slideLength + 0.90625),
                'min_cabinet_depth_shop' => $slideLength + self::SHOP_MIN_DEPTH_ADDITION,
            ],
            'validation' => $this->validateDimensions($openingWidth, $openingHeight, $openingDepth, $specs, $drawerSideThickness),
        ];
    }

    /**
     * Calculate dimensions for multiple drawers in a stack.
     * 
     * @param float $openingWidth Cabinet opening width
     * @param float $totalHeight Total height available for drawers
     * @param float $openingDepth Cabinet opening depth
     * @param int $drawerCount Number of drawers
     * @param array|null $heightDistribution Optional array of height percentages per drawer
     * @return array Dimensions for each drawer
     */
    public function calculateDrawerStack(
        float $openingWidth,
        float $totalHeight,
        float $openingDepth,
        int $drawerCount,
        ?array $heightDistribution = null
    ): array {
        $results = [];
        
        // Get slide specs once (same slide for all drawers)
        $slide = $this->hardwareService->getSlideProductForDepth($openingDepth);
        $specs = $slide ? $this->hardwareService->getSlideSpecs($slide) : $this->getDefaultSpecs();

        $topGap = $specs['top_clearance'] ?? 0.75;
        $bottomGap = $specs['bottom_clearance'] ?? 0.5;
        $verticalGapPerDrawer = $topGap + $bottomGap;

        // Total height consumed by gaps between drawers
        $totalGapHeight = $verticalGapPerDrawer * $drawerCount;
        $availableBoxHeight = $totalHeight - $totalGapHeight;

        // Distribute height
        if ($heightDistribution === null) {
            // Equal distribution
            $heightDistribution = array_fill(0, $drawerCount, 1 / $drawerCount);
        }

        $currentPosition = 0;
        foreach ($heightDistribution as $index => $ratio) {
            $drawerOpeningHeight = ($availableBoxHeight * $ratio) + $verticalGapPerDrawer;
            
            $drawerDims = $this->calculateDrawerDimensions(
                $openingWidth,
                $drawerOpeningHeight,
                $openingDepth
            );

            $drawerDims['position'] = [
                'index' => $index + 1,
                'from_bottom' => round($currentPosition, 4),
            ];

            $results[] = $drawerDims;
            $currentPosition += $drawerOpeningHeight;
        }

        return [
            'drawers' => $results,
            'summary' => [
                'drawer_count' => $drawerCount,
                'total_height' => $totalHeight,
                'gap_height_used' => $totalGapHeight,
                'box_height_available' => $availableBoxHeight,
            ],
        ];
    }

    /**
     * Validate if dimensions meet minimum requirements.
     */
    protected function validateDimensions(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        array $specs,
        float $drawerSideThickness = 0.5
    ): array {
        $issues = [];
        $valid = true;

        // Check minimum cabinet depth
        $minDepth = $specs['min_cabinet_depth'] ?? 13;
        if ($openingDepth < $minDepth) {
            $issues[] = "Cabinet depth ({$openingDepth}\") is less than minimum required ({$minDepth}\")";
            $valid = false;
        }

        // Check minimum width for drawer (typically 3" minimum)
        $sideDeduction = $drawerSideThickness >= 0.625 
            ? self::SIDE_DEDUCTION_5_8 
            : self::SIDE_DEDUCTION_1_2;
        $minBoxWidth = 3.0;
        $calculatedWidth = $openingWidth - $sideDeduction;
        if ($calculatedWidth < $minBoxWidth) {
            $issues[] = "Calculated drawer width ({$calculatedWidth}\") is too narrow (min {$minBoxWidth}\")";
            $valid = false;
        }

        // Check minimum height for drawer (typically 2" minimum)
        $topGap = $specs['top_clearance'] ?? 0.25;
        $bottomGap = $specs['bottom_clearance'] ?? 0.5625;
        $minBoxHeight = 2.0;
        $calculatedHeight = $openingHeight - $topGap - $bottomGap;
        if ($calculatedHeight < $minBoxHeight) {
            $issues[] = "Calculated drawer height ({$calculatedHeight}\") is too short (min {$minBoxHeight}\")";
            $valid = false;
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
        ];
    }

    /**
     * Default specs when no slide product is found (Blum TANDEM 563H values).
     */
    protected function getDefaultSpecs(): array
    {
        return [
            'length' => 18,
            'min_cabinet_depth' => 19,
            'weight_capacity' => 90,
            'top_clearance' => 0.25,      // 6mm (1/4")
            'bottom_clearance' => 0.5625, // 14mm (9/16")
        ];
    }

    /**
     * Fallback calculation when no slide product is found.
     */
    protected function getDefaultCalculation(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        float $drawerSideThickness = 0.5
    ): array {
        $specs = $this->getDefaultSpecs();
        
        // Determine appropriate slide length based on depth
        $slideLength = match (true) {
            $openingDepth >= 21 => 21,
            $openingDepth >= 18 => 18,
            $openingDepth >= 15 => 15,
            default => 12,
        };

        // Specs based on drawer side thickness
        $is5_8Sides = $drawerSideThickness >= 0.625;
        
        $sideDeduction = $is5_8Sides ? self::SIDE_DEDUCTION_5_8 : self::SIDE_DEDUCTION_1_2;
        $insideWidthDeduction = $is5_8Sides ? self::INSIDE_WIDTH_DEDUCTION_5_8 : self::INSIDE_WIDTH_DEDUCTION_1_2;
        $bottomRecess = $is5_8Sides ? self::BOTTOM_RECESS_5_8 : self::BOTTOM_RECESS_1_2;

        $boxOutsideWidth = $openingWidth - $sideDeduction;
        $boxInsideWidth = $openingWidth - $insideWidthDeduction;
        $boxHeight = $openingHeight - self::HEIGHT_DEDUCTION;
        // Drawer depth = slide length (per Blum spec)
        $boxDepth = $slideLength;
        
        // Shop height: rounded DOWN to nearest 1/2" for safety
        $boxHeightShop = self::roundDownToHalfInch($boxHeight);
        
        // Shop depth: slide length + 1/4" for safety clearance
        $boxDepthShop = $boxDepth + self::SHOP_DEPTH_ADDITION;

        return [
            'opening' => [
                'width' => $openingWidth,
                'height' => $openingHeight,
                'depth' => $openingDepth,
            ],
            'drawer_box' => [
                'outside_width' => round($boxOutsideWidth, 4),
                'inside_width' => round($boxInsideWidth, 4),
                'height' => round($boxHeight, 4),
                'height_shop' => $boxHeightShop,  // Rounded down for shop use
                'depth' => round($boxDepth, 4),
                'depth_shop' => round($boxDepthShop, 4),  // +1/4" for shop use
            ],
            'drawer_side_thickness' => $drawerSideThickness,
            'bottom_panel' => [
                'recess_from_bottom' => $bottomRecess,
                'width' => round($boxInsideWidth, 4),
                'depth' => round($boxDepth - $bottomRecess, 4),
            ],
            'clearances' => [
                'side_deduction' => $sideDeduction,
                'inside_width_deduction' => $insideWidthDeduction,
                'top' => self::TOP_CLEARANCE,
                'bottom' => self::BOTTOM_CLEARANCE,
                'height_deduction' => self::HEIGHT_DEDUCTION,
            ],
            'hardware' => [
                'slide_product_id' => null,
                'slide_name' => 'Default (no product found)',
                'slide_length' => $slideLength,
                'weight_capacity' => $specs['weight_capacity'],
                'min_cabinet_depth' => $specs['min_cabinet_depth'],
                'min_cabinet_depth_blum' => self::BLUM_MIN_CABINET_DEPTHS[$slideLength] ?? ($slideLength + 0.90625),
                'min_cabinet_depth_shop' => $slideLength + self::SHOP_MIN_DEPTH_ADDITION,
            ],
            'validation' => $this->validateDimensions($openingWidth, $openingHeight, $openingDepth, $specs, $drawerSideThickness),
            'warning' => 'Using default specifications - no slide product found for this depth',
        ];
    }

    /**
     * Calculate drawer dimensions for a cabinet section.
     * 
     * @param int $sectionId The cabinet section ID
     * @return array|null Calculated dimensions or null if section not found
     */
    public function calculateForSection(int $sectionId): ?array
    {
        $section = \DB::table('projects_cabinet_sections')->find($sectionId);
        
        if (!$section) {
            return null;
        }

        return $this->calculateDrawerDimensions(
            $section->opening_width_inches ?? $section->width_inches ?? 12,
            $section->opening_height_inches ?? $section->height_inches ?? 6,
            $section->opening_depth_inches ?? 18
        );
    }

    /**
     * Get a quick quote for drawer hardware based on cabinet dimensions.
     */
    public function getQuickQuote(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        int $drawerCount = 1,
        float $drawerSideThickness = 0.5
    ): array {
        $dimensions = $this->calculateDrawerDimensions($openingWidth, $openingHeight, $openingDepth, $drawerSideThickness);
        
        // Use opening depth for hardware selection (not box depth)
        // This ensures consistent slide selection with dimension calculation
        $hardware = $this->hardwareService->getHardwareForDrawers(
            $openingDepth,
            $drawerCount
        );

        return [
            'dimensions' => $dimensions,
            'hardware' => $hardware,
        ];
    }

    /**
     * Generate a complete cut list for dovetail drawer box construction.
     * 
     * Construction method:
     * - Dovetail joinery (sides extend past front/back)
     * - 1/2" material for sides, front, back
     * - 1/4" bottom in 1/4" deep dado, 1/2" up from bottom
     * - False front applied separately
     * 
     * @param float $openingWidth Cabinet opening width
     * @param float $openingHeight Cabinet opening height  
     * @param float $openingDepth Cabinet opening depth
     * @param float $drawerSideThickness Material thickness (default 0.5")
     * @return array Complete cut list with all pieces
     */
    public function getCutList(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        float $drawerSideThickness = 0.5
    ): array {
        $dims = $this->calculateDrawerDimensions($openingWidth, $openingHeight, $openingDepth, $drawerSideThickness);
        
        $boxWidth = $dims['drawer_box']['outside_width'];
        $boxHeight = $dims['drawer_box']['height'];
        $boxHeightShop = $dims['drawer_box']['height_shop'];
        $boxDepth = $dims['drawer_box']['depth'];
        $boxDepthShop = $dims['drawer_box']['depth_shop'];
        $materialThickness = self::MATERIAL_THICKNESS;
        
        // Dovetail construction: sides are full depth, front/back fit between
        $sideLength = $boxDepth;
        $sideLengthShop = $boxDepthShop;  // Shop version: +1/4"
        $sideHeight = $boxHeight;
        $sideHeightShop = $boxHeightShop;  // Shop version rounded down
        
        // Front/back width = box width minus both side thicknesses
        $frontBackWidth = $boxWidth - (2 * $materialThickness);
        $frontBackHeight = $boxHeight;
        $frontBackHeightShop = $boxHeightShop;  // Shop version rounded down
        
        // Bottom panel sits in dado grooves
        // Width: spans between dados in sides (inside face + dado depth on each side)
        $bottomWidth = $boxWidth - (2 * $materialThickness) + (2 * self::DADO_DEPTH) - self::BOTTOM_CLEARANCE_IN_DADO;
        // Depth: spans between dados in front/back
        $bottomDepth = $boxDepth - (2 * $materialThickness) + (2 * self::DADO_DEPTH) - self::BOTTOM_CLEARANCE_IN_DADO;
        
        return [
            'opening' => $dims['opening'],
            'drawer_box' => $dims['drawer_box'],
            'cut_list' => [
                'sides' => [
                    'quantity' => 2,
                    'material' => '1/2" plywood',
                    'width' => round($sideHeight, 4),           // Theoretical height
                    'width_shop' => $sideHeightShop,            // Shop: rounded down to 1/2"
                    'length' => round($sideLength, 4),          // Theoretical length (slide length)
                    'length_shop' => round($sideLengthShop, 4), // Shop: +1/4" for safety
                    'notes' => 'Dovetail tails on front/back edges',
                ],
                'front' => [
                    'quantity' => 1,
                    'material' => '1/2" plywood',
                    'width' => round($frontBackHeight, 4),      // Theoretical
                    'width_shop' => $frontBackHeightShop,       // Shop: rounded down to 1/2"
                    'length' => round($frontBackWidth, 4),
                    'notes' => 'Dovetail pins on ends, fits between sides',
                ],
                'back' => [
                    'quantity' => 1,
                    'material' => '1/2" plywood',
                    'width' => round($frontBackHeight, 4),      // Theoretical
                    'width_shop' => $frontBackHeightShop,       // Shop: rounded down to 1/2"
                    'length' => round($frontBackWidth, 4),
                    'notes' => 'Dovetail pins on ends, fits between sides',
                ],
                'bottom' => [
                    'quantity' => 1,
                    'material' => '1/4" plywood',
                    'width' => round($bottomWidth, 4),
                    'length' => round($bottomDepth, 4),
                    'notes' => 'Sits in 1/4" dado, 1/2" up from bottom edge',
                ],
            ],
            'dado_specs' => [
                'depth' => self::DADO_DEPTH,
                'width' => self::BOTTOM_THICKNESS,
                'height_from_bottom' => self::BOTTOM_DADO_HEIGHT,
                'notes' => 'Cut dado in all 4 pieces before assembly',
            ],
            'hardware' => $dims['hardware'],
            'validation' => $dims['validation'],
        ];
    }

    /**
     * Format a dimension as a fractional string.
     * 
     * @param float $decimal Decimal inches
     * @param int $denominator Fraction denominator (default 32)
     * @return string Fractional representation (e.g., "11-3/8\"")
     */
    public static function toFraction(float $decimal, int $denominator = 32): string
    {
        $whole = floor($decimal);
        $remainder = $decimal - $whole;
        $numerator = round($remainder * $denominator);
        
        if ($numerator == 0) {
            return $whole . '"';
        }
        if ($numerator == $denominator) {
            return ($whole + 1) . '"';
        }
        
        // Simplify fraction
        $gcd = self::gcd((int)$numerator, $denominator);
        $num = $numerator / $gcd;
        $den = $denominator / $gcd;
        
        return $whole > 0 ? "{$whole}-{$num}/{$den}\"" : "{$num}/{$den}\"";
    }

    /**
     * Greatest common divisor for fraction simplification.
     */
    private static function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : self::gcd($b, $a % $b);
    }

    /**
     * Round a dimension DOWN to the nearest 1/2 inch for shop use.
     * 
     * This is a common safety practice in woodworking shops:
     * - Theoretical: 5.1875" (5-3/16") → Shop: 5.0" (5")
     * - Theoretical: 5.75" (5-3/4") → Shop: 5.5" (5-1/2")
     * - Theoretical: 6.25" (6-1/4") → Shop: 6.0" (6")
     * 
     * Rounding down ensures drawers always fit with adequate clearance.
     * 
     * @param float $inches Theoretical dimension in inches
     * @return float Dimension rounded down to nearest 1/2"
     */
    public static function roundDownToHalfInch(float $inches): float
    {
        return floor($inches * 2) / 2;
    }

    /**
     * Get both theoretical and shop heights for a given opening height.
     * 
     * @param float $openingHeight Cabinet opening height
     * @return array ['theoretical' => float, 'shop' => float]
     */
    public function getHeightCalculations(float $openingHeight): array
    {
        $theoretical = $openingHeight - self::HEIGHT_DEDUCTION;
        $shop = self::roundDownToHalfInch($theoretical);
        
        return [
            'theoretical' => round($theoretical, 4),
            'shop' => $shop,
            'theoretical_fraction' => self::toFraction($theoretical),
            'shop_fraction' => self::toFraction($shop),
            'reduction' => round($theoretical - $shop, 4),
        ];
    }

    /**
     * Get minimum cabinet depth for a given slide length.
     * 
     * @param int $slideLength Slide length in inches
     * @return array ['blum' => float, 'shop' => float, 'blum_fraction' => string, 'shop_fraction' => string]
     */
    public static function getMinCabinetDepth(int $slideLength): array
    {
        $blum = self::BLUM_MIN_CABINET_DEPTHS[$slideLength] ?? ($slideLength + 0.90625);
        $shop = $slideLength + self::SHOP_MIN_DEPTH_ADDITION;
        
        return [
            'blum' => $blum,
            'shop' => $shop,
            'blum_fraction' => self::toFraction($blum),
            'shop_fraction' => self::toFraction($shop),
            'slide_length' => $slideLength,
        ];
    }

    /**
     * Get all minimum cabinet depths for reference.
     * 
     * @return array Table of slide lengths with Blum and shop minimums
     */
    public static function getAllMinCabinetDepths(): array
    {
        $results = [];
        foreach (self::BLUM_MIN_CABINET_DEPTHS as $slideLength => $blumMin) {
            $shopMin = $slideLength + self::SHOP_MIN_DEPTH_ADDITION;
            $results[$slideLength] = [
                'slide_length' => $slideLength,
                'blum' => $blumMin,
                'blum_fraction' => self::toFraction($blumMin),
                'shop' => $shopMin,
                'shop_fraction' => self::toFraction($shopMin),
            ];
        }
        return $results;
    }

    /**
     * Get a formatted cut list with fractional dimensions.
     */
    public function getFormattedCutList(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        float $drawerSideThickness = 0.5
    ): array {
        $cutList = $this->getCutList($openingWidth, $openingHeight, $openingDepth, $drawerSideThickness);
        
        $formatted = [
            'opening' => sprintf('%s W × %s H × %s D',
                self::toFraction($openingWidth),
                self::toFraction($openingHeight),
                self::toFraction($openingDepth)
            ),
            'drawer_box' => [
                'theoretical' => sprintf('%s W × %s H × %s D',
                    self::toFraction($cutList['drawer_box']['outside_width']),
                    self::toFraction($cutList['drawer_box']['height']),
                    self::toFraction($cutList['drawer_box']['depth'])
                ),
                'shop' => sprintf('%s W × %s H × %s D',
                    self::toFraction($cutList['drawer_box']['outside_width']),
                    self::toFraction($cutList['drawer_box']['height_shop']),
                    self::toFraction($cutList['drawer_box']['depth'])
                ),
            ],
            'pieces' => [],
        ];

        foreach ($cutList['cut_list'] as $piece => $spec) {
            $shopHeight = $spec['width_shop'] ?? $spec['width'];  // Bottom doesn't have shop version
            $formatted['pieces'][$piece] = [
                'qty' => $spec['quantity'],
                'size_theoretical' => self::toFraction($spec['width']) . ' × ' . self::toFraction($spec['length']),
                'size_shop' => self::toFraction($shopHeight) . ' × ' . self::toFraction($spec['length']),
                'material' => $spec['material'],
                'notes' => $spec['notes'],
            ];
        }

        $formatted['dado'] = sprintf('%s deep × %s wide, %s up from bottom',
            self::toFraction($cutList['dado_specs']['depth']),
            self::toFraction($cutList['dado_specs']['width']),
            self::toFraction($cutList['dado_specs']['height_from_bottom'])
        );

        $formatted['slide'] = $cutList['hardware']['slide_name'] ?? 'N/A';

        return $formatted;
    }

    // ========================================
    // FACE FRAME STYLE METHODS
    // ========================================

    /**
     * Get all available face frame styles for user selection.
     *
     * Returns array suitable for FilamentPHP Select options:
     * ['full_overlay' => 'Full Overlay', 'inset' => 'Inset', ...]
     *
     * @return array
     */
    public static function getFaceFrameStyleOptions(): array
    {
        $options = [];
        foreach (self::FACE_FRAME_STYLES as $key => $style) {
            $options[$key] = $style['name'];
        }
        return $options;
    }

    /**
     * Get detailed information about a face frame style.
     *
     * @param string $style Style key (full_overlay, inset, etc.)
     * @return array|null Style details or null if not found
     */
    public static function getFaceFrameStyleInfo(string $style): ?array
    {
        return self::FACE_FRAME_STYLES[$style] ?? null;
    }

    /**
     * Determine if a rail is needed for a given gap based on face frame style.
     *
     * This is the main method for face frame collision detection:
     * - Full Overlay: Rails only for gaps >= 3/4" (components can be close together)
     * - Inset: Rails ALWAYS (each opening needs to be defined)
     * - Partial Overlay: Rails ALWAYS (frame each opening)
     * - Frameless: No rails (no face frame at all)
     *
     * @param float $gapSize Gap between components in inches
     * @param string $style Face frame style
     * @return bool True if a rail should be added
     */
    public static function needsRailForGap(float $gapSize, string $style = self::FACE_FRAME_STYLE_FULL_OVERLAY): bool
    {
        // Delegate to Faceframe model for consistency
        return Faceframe::needsRailForGapStatic($gapSize, $style);
    }

    /**
     * Get face frame requirements for a drawer configuration.
     *
     * This provides all the face frame information a user needs when
     * configuring drawers. Should be called when calculating drawer dimensions
     * to inform the user about rail requirements.
     *
     * @param string $style Face frame style
     * @param array $components Array of components with heights and positions
     * @param float $openingHeight Total opening height
     * @return array Face frame requirements and recommendations
     */
    public function getFaceFrameRequirements(
        string $style,
        array $components = [],
        float $openingHeight = 0
    ): array {
        $styleInfo = self::getFaceFrameStyleInfo($style) ?? self::FACE_FRAME_STYLES[self::FACE_FRAME_STYLE_FULL_OVERLAY];

        $result = [
            'style' => $style,
            'style_name' => $styleInfo['name'],
            'style_description' => $styleInfo['description'],
            'has_face_frame' => $styleInfo['has_face_frame'],
            'rails_required' => $styleInfo['rails_required'],
            'min_rail_gap' => $styleInfo['min_rail_gap'],
            'rails' => [],
            'recommendations' => [],
        ];

        // If frameless, no face frame calculations needed
        if (!$styleInfo['has_face_frame']) {
            $result['recommendations'][] = 'Frameless construction: No face frame needed. Doors mount directly to cabinet sides.';
            return $result;
        }

        // Analyze gaps between components if provided
        if (!empty($components) && $openingHeight > 0) {
            $result['rails'] = $this->analyzeRailRequirements($components, $openingHeight, $style);

            // Generate recommendations based on analysis
            if (empty($result['rails']['needed'])) {
                if ($styleInfo['rails_required'] === 'conditional') {
                    $result['recommendations'][] = 'Full overlay: No mid-rails needed. Drawer faces can be placed close together.';
                }
            } else {
                foreach ($result['rails']['needed'] as $rail) {
                    $result['recommendations'][] = sprintf(
                        'Rail needed at Y=%.3f" (gap of %.3f" between %s)',
                        $rail['position'],
                        $rail['gap'],
                        $rail['between']
                    );
                }
            }
        }

        // Add general recommendations based on style
        switch ($style) {
            case self::FACE_FRAME_STYLE_FULL_OVERLAY:
                $result['recommendations'][] = 'TCS Standard: Use 1/8" reveal gap between drawer faces.';
                break;
            case self::FACE_FRAME_STYLE_INSET:
                $result['recommendations'][] = 'Inset style: Ensure precise fit. Use 1/16" clearance around doors/drawers.';
                break;
            case self::FACE_FRAME_STYLE_PARTIAL_OVERLAY:
                $result['recommendations'][] = 'Partial overlay: Plan for visible frame reveal around each component.';
                break;
        }

        return $result;
    }

    /**
     * Analyze which rails are needed based on component layout.
     *
     * @param array $components Array of components with 'height' and optionally 'y_position'
     * @param float $openingHeight Total opening height
     * @param string $style Face frame style
     * @return array Analysis of rail requirements
     */
    protected function analyzeRailRequirements(array $components, float $openingHeight, string $style): array
    {
        $result = [
            'needed' => [],
            'gaps' => [],
            'top_rail' => true,  // Always need top rail
            'bottom_rail' => true, // May not be needed for full overlay
        ];

        // Calculate positions if not provided
        $positioned = [];
        $currentY = 0;
        $revealGap = 0.125; // 1/8" standard gap

        foreach ($components as $i => $component) {
            $height = $component['height'] ?? $component['front_height_inches'] ?? 0;
            $y = $component['y_position'] ?? $currentY;

            $positioned[] = [
                'index' => $i,
                'name' => $component['name'] ?? "Component " . ($i + 1),
                'y_bottom' => $y,
                'y_top' => $y + $height,
                'height' => $height,
            ];

            $currentY = $y + $height + $revealGap;
        }

        // Sort by Y position (bottom to top)
        usort($positioned, fn($a, $b) => $a['y_bottom'] <=> $b['y_bottom']);

        // Analyze gaps
        for ($i = 0; $i < count($positioned) - 1; $i++) {
            $lower = $positioned[$i];
            $upper = $positioned[$i + 1];

            $gap = $upper['y_bottom'] - $lower['y_top'];

            $result['gaps'][] = [
                'between' => $lower['name'] . ' and ' . $upper['name'],
                'gap' => $gap,
                'needs_rail' => self::needsRailForGap($gap, $style),
            ];

            if (self::needsRailForGap($gap, $style)) {
                $result['needed'][] = [
                    'position' => $lower['y_top'] + ($gap / 2),
                    'gap' => $gap,
                    'between' => $lower['name'] . ' and ' . $upper['name'],
                ];
            }
        }

        // Check bottom gap
        if (!empty($positioned)) {
            $lowestComponent = $positioned[0];
            $bottomGap = $lowestComponent['y_bottom'];

            if (!self::needsRailForGap($bottomGap, $style)) {
                $result['bottom_rail'] = false;
            }
        }

        return $result;
    }

    /**
     * Calculate drawer dimensions with face frame requirements.
     *
     * Enhanced version of calculateDrawerDimensions that includes
     * face frame style information in the output.
     *
     * @param float $openingWidth Cabinet opening width in inches
     * @param float $openingHeight Cabinet opening height in inches
     * @param float $openingDepth Cabinet opening depth in inches
     * @param float $drawerSideThickness Drawer side material thickness
     * @param string $faceFrameStyle Face frame style
     * @return array Calculated drawer dimensions with face frame info
     */
    public function calculateDrawerDimensionsWithFaceFrame(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        float $drawerSideThickness = 0.5,
        string $faceFrameStyle = self::FACE_FRAME_STYLE_FULL_OVERLAY
    ): array {
        // Get standard drawer dimensions
        $dimensions = $this->calculateDrawerDimensions(
            $openingWidth,
            $openingHeight,
            $openingDepth,
            $drawerSideThickness
        );

        // Add face frame style information
        $dimensions['face_frame'] = $this->getFaceFrameRequirements(
            $faceFrameStyle,
            [['height' => $openingHeight, 'name' => 'Drawer']],
            $openingHeight
        );

        return $dimensions;
    }

    /**
     * Calculate drawer stack with face frame requirements.
     *
     * Enhanced version of calculateDrawerStack that analyzes
     * rail requirements between drawers.
     *
     * @param float $openingWidth Cabinet opening width
     * @param float $totalHeight Total height available for drawers
     * @param float $openingDepth Cabinet opening depth
     * @param int $drawerCount Number of drawers
     * @param array|null $heightDistribution Optional array of height percentages
     * @param string $faceFrameStyle Face frame style
     * @return array Dimensions for each drawer with face frame info
     */
    public function calculateDrawerStackWithFaceFrame(
        float $openingWidth,
        float $totalHeight,
        float $openingDepth,
        int $drawerCount,
        ?array $heightDistribution = null,
        string $faceFrameStyle = self::FACE_FRAME_STYLE_FULL_OVERLAY
    ): array {
        // Get standard stack dimensions
        $stack = $this->calculateDrawerStack(
            $openingWidth,
            $totalHeight,
            $openingDepth,
            $drawerCount,
            $heightDistribution
        );

        // Build components array for face frame analysis
        $components = [];
        $revealGap = 0.125;
        $currentY = $revealGap; // Start with bottom reveal

        foreach ($stack['drawers'] as $i => $drawer) {
            $height = $drawer['opening']['height'];
            $components[] = [
                'name' => 'Drawer ' . ($i + 1),
                'height' => $height,
                'y_position' => $currentY,
            ];
            $currentY += $height + $revealGap;
        }

        // Add face frame analysis
        $stack['face_frame'] = $this->getFaceFrameRequirements(
            $faceFrameStyle,
            $components,
            $totalHeight
        );

        return $stack;
    }
}
