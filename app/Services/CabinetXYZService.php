<?php

namespace App\Services;

/**
 * Cabinet XYZ Coordinate Service
 *
 * Generates 3D positions for all cabinet parts.
 *
 * COORDINATE SYSTEM:
 * - Origin: Front-Bottom-Left corner of cabinet BOX
 * - X: Left → Right (positive)
 * - Y: Bottom → Top (positive) - Y=0 is BOTTOM of box
 * - Z: Front → Back (positive)
 *
 * - Toe kick is in NEGATIVE Y (below the box)
 * - All positions use the part's bottom-left-front corner as reference
 *
 * This coordinate system ensures:
 * 1. Bottom of bottom panel is at Y=0
 * 2. Parts "touch and add up" - dimensions are traceable
 * 3. Direct export to CAD systems (Rhino Z-up after simple axis swap)
 */
class CabinetXYZService
{
    protected ?JointCalculatorService $jointCalculator = null;

    /**
     * Get or create the JointCalculatorService instance.
     */
    protected function getJointCalculator(): JointCalculatorService
    {
        if ($this->jointCalculator === null) {
            $this->jointCalculator = new JointCalculatorService();
        }
        return $this->jointCalculator;
    }
    // Material thicknesses
    public const PLYWOOD_3_4 = 0.75;
    public const PLYWOOD_1_2 = 0.5;
    public const PLYWOOD_1_4 = 0.25;
    public const DRAWER_FACE_THICKNESS = 1.0;  // TCS standard: 1" thick drawer faces

    // Blum TANDEM clearances - reference DrawerConfiguratorService for authoritative values
    public const BLUM_SIDE_DEDUCTION = 0.625;    // 5/8" total (drawer narrower than opening)
    public const BLUM_TOP_CLEARANCE = 0.25;       // 1/4" above drawer box
    public const BLUM_BOTTOM_CLEARANCE = 0.5625;  // 9/16" below drawer box

    // Standard gaps and reveals
    public const COMPONENT_GAP = 0.125;  // 1/8" between components
    public const DRAWER_BOTTOM_REVEAL = 0.5;  // 1/2" clearance from cabinet box bottom to bottom drawer face

    // Face frame style constants
    public const STYLE_FRAMELESS = 'frameless';           // European/modern - no face frame
    public const STYLE_FACE_FRAME = 'face_frame';         // Traditional American with full frame
    public const STYLE_FULL_OVERLAY = 'full_overlay';     // Modern look with frame strength (TCS default)
    public const STYLE_INSET = 'inset';                   // High-end traditional - faces flush in frame
    public const STYLE_PARTIAL_OVERLAY = 'partial_overlay'; // Standard/economical

    /**
     * Calculate drawer box individual part positions for 3D rendering.
     *
     * Uses DrawerConfiguratorService constants for all dimensions.
     * Dovetail construction: sides are full depth, front/back fit between sides.
     * Bottom panel sits in dado grooves cut in all 4 pieces.
     *
     * DADO LOGIC:
     * 1. Dado is cut into all 4 pieces (sides, front, back)
     * 2. Dado width = bottom thickness (1/4")
     * 3. Dado depth = how far into material (1/4")
     * 4. Dado height = distance from bottom edge (1/2")
     * 5. Bottom panel = fills the void created by dados
     *    - Extends into each dado by dado_depth
     *    - Minus small clearance so it slides in
     *
     * @param float $boxX Starting X position of drawer box
     * @param float $boxY Starting Y position (bottom of box)
     * @param float $boxZ Starting Z position (front of box)
     * @param float $boxWidth Outside width of drawer box
     * @param float $boxHeight Height of drawer box
     * @param float $boxDepth Depth of drawer box (slide length)
     * @param string $drawerLabel Label for part names (e.g., "Upper Drawer")
     * @return array Individual parts with positions and dimensions
     */
    public function calculateDrawerBoxParts(
        float $boxX,
        float $boxY,
        float $boxZ,
        float $boxWidth,
        float $boxHeight,
        float $boxDepth,
        string $drawerLabel = 'Drawer'
    ): array {
        // Get constants from DrawerConfiguratorService
        $material = DrawerConfiguratorService::MATERIAL_THICKNESS;      // 0.5"
        $bottomThickness = DrawerConfiguratorService::BOTTOM_THICKNESS; // 0.25"
        $dadoDepth = DrawerConfiguratorService::DADO_DEPTH;             // 0.25"
        $dadoHeight = DrawerConfiguratorService::BOTTOM_DADO_HEIGHT;    // 0.5"
        $dadoClearance = DrawerConfiguratorService::BOTTOM_CLEARANCE_IN_DADO; // 0.0625"

        // ===========================================
        // STEP 1: Calculate box internal dimensions
        // ===========================================
        $insideWidth = $boxWidth - (2 * $material);   // Space between sides
        $insideDepth = $boxDepth - (2 * $material);   // Space between front/back

        // ===========================================
        // STEP 2: Calculate bottom panel from dado
        // Bottom fills the void: inside + dado extensions - clearance
        // ===========================================
        $bottomWidth = $insideWidth + (2 * $dadoDepth) - $dadoClearance;
        $bottomDepth = $insideDepth + (2 * $dadoDepth) - $dadoClearance;

        // ===========================================
        // STEP 3: Calculate part positions
        // ===========================================

        // Sides (full depth, dovetail tails)
        $leftSideX = $boxX;
        $rightSideX = $boxX + $boxWidth - $material;
        $sidesY = $boxY;
        $sidesZ = $boxZ;

        // Front/back (fit between sides, dovetail pins)
        $frontBackX = $boxX + $material;
        $frontBackWidth = $insideWidth;  // Same as inside width
        $frontBackY = $boxY;
        $frontZ = $boxZ;
        $backZ = $boxZ + $boxDepth - $material;

        // Bottom (sits in dado groove, extends into each dado)
        // Position is where dado starts (inside face minus dado depth)
        // Clearance is already in the SIZE calculation, not the position
        $bottomX = $boxX + $material - $dadoDepth;  // 1.0625 + 0.5 - 0.25 = 1.3125
        $bottomY = $boxY + $dadoHeight;  // Dado height from bottom (0.5625 + 0.5 = 1.0625)
        $bottomZ = $boxZ + $material - $dadoDepth;  // 0.75 + 0.5 - 0.25 = 1.0

        return [
            'left_side' => [
                'part_name' => "{$drawerLabel} Box Left Side",
                'part_type' => 'drawer_box',
                'position' => ['x' => $leftSideX, 'y' => $sidesY, 'z' => $sidesZ],
                'dimensions' => ['w' => $material, 'h' => $boxHeight, 'd' => $boxDepth],
                'cut_dimensions' => ['width' => $boxHeight, 'length' => $boxDepth, 'thickness' => $material],
                'dado' => $this->calculateDadoSpec($dadoDepth, $bottomThickness, $dadoHeight),
            ],
            'right_side' => [
                'part_name' => "{$drawerLabel} Box Right Side",
                'part_type' => 'drawer_box',
                'position' => ['x' => $rightSideX, 'y' => $sidesY, 'z' => $sidesZ],
                'dimensions' => ['w' => $material, 'h' => $boxHeight, 'd' => $boxDepth],
                'cut_dimensions' => ['width' => $boxHeight, 'length' => $boxDepth, 'thickness' => $material],
                'dado' => $this->calculateDadoSpec($dadoDepth, $bottomThickness, $dadoHeight),
            ],
            'front' => [
                'part_name' => "{$drawerLabel} Box Front",
                'part_type' => 'drawer_box',
                'position' => ['x' => $frontBackX, 'y' => $frontBackY, 'z' => $frontZ],
                'dimensions' => ['w' => $frontBackWidth, 'h' => $boxHeight, 'd' => $material],
                'cut_dimensions' => ['width' => $boxHeight, 'length' => $frontBackWidth, 'thickness' => $material],
                'dado' => $this->calculateDadoSpec($dadoDepth, $bottomThickness, $dadoHeight),
            ],
            'back' => [
                'part_name' => "{$drawerLabel} Box Back",
                'part_type' => 'drawer_box',
                'position' => ['x' => $frontBackX, 'y' => $frontBackY, 'z' => $backZ],
                'dimensions' => ['w' => $frontBackWidth, 'h' => $boxHeight, 'd' => $material],
                'cut_dimensions' => ['width' => $boxHeight, 'length' => $frontBackWidth, 'thickness' => $material],
                'dado' => $this->calculateDadoSpec($dadoDepth, $bottomThickness, $dadoHeight),
            ],
            'bottom' => [
                'part_name' => "{$drawerLabel} Box Bottom",
                'part_type' => 'drawer_box_bottom',
                'position' => ['x' => $bottomX, 'y' => $bottomY, 'z' => $bottomZ],
                'dimensions' => ['w' => $bottomWidth, 'h' => $bottomThickness, 'd' => $bottomDepth],
                'cut_dimensions' => ['width' => $bottomWidth, 'length' => $bottomDepth, 'thickness' => $bottomThickness],
                'note' => 'Fills dado void - slides in from back during assembly',
            ],
        ];
    }

    /**
     * Calculate dado specification for drawer box pieces.
     *
     * The dado creates a groove that the bottom panel sits in.
     * All 4 pieces (2 sides, front, back) get the same dado.
     *
     * @param float $depth How far into the material (typically 1/4")
     * @param float $width Width of groove (matches bottom thickness, typically 1/4")
     * @param float $heightFromBottom Distance from bottom edge to dado (typically 1/2")
     * @return array Dado specification
     */
    public function calculateDadoSpec(float $depth, float $width, float $heightFromBottom): array
    {
        return [
            'depth' => $depth,
            'width' => $width,
            'height_from_bottom' => $heightFromBottom,
            'description' => sprintf(
                '%s" deep × %s" wide, %s" up from bottom edge',
                $this->toFraction($depth),
                $this->toFraction($width),
                $this->toFraction($heightFromBottom)
            ),
        ];
    }

    /**
     * Convert decimal inches to fraction string.
     *
     * @param float $decimal Decimal inches
     * @return string Fractional representation
     */
    protected function toFraction(float $decimal): string
    {
        $whole = floor($decimal);
        $remainder = $decimal - $whole;

        if ($remainder < 0.01) {
            return $whole > 0 ? (string)$whole : '0';
        }

        // Common fractions
        $fractions = [
            0.0625 => '1/16', 0.125 => '1/8', 0.1875 => '3/16',
            0.25 => '1/4', 0.3125 => '5/16', 0.375 => '3/8',
            0.4375 => '7/16', 0.5 => '1/2', 0.5625 => '9/16',
            0.625 => '5/8', 0.6875 => '11/16', 0.75 => '3/4',
            0.8125 => '13/16', 0.875 => '7/8', 0.9375 => '15/16',
        ];

        $closest = null;
        $minDiff = 1;
        foreach ($fractions as $val => $frac) {
            $diff = abs($remainder - $val);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $frac;
            }
        }

        return $whole > 0 ? "{$whole}-{$closest}" : $closest;
    }

    /**
     * Get face frame configuration based on style.
     *
     * Each style defines different rules for stiles, rails, and drawer/door face positioning.
     * Values are pulled from $specs (Construction Template) with fallbacks to ConstructionStandardsService.
     *
     * STYLE DESCRIPTIONS:
     * - frameless: European/modern - no face frame, faces flush with box edges
     * - face_frame: Traditional American - full frame with faces inside rail openings
     * - full_overlay: Modern look - stiles present, rails optional, faces overlay frame
     * - inset: Premium traditional - full frame with faces flush inside openings
     * - partial_overlay: Standard/economical - full frame with partial face overlap
     *
     * @param string $style Face frame style constant
     * @param array $specs Cabinet specs array (from Construction Template)
     * @return array Configuration for stiles, rails, face positioning, and overlay amounts
     */
    public function getFaceFrameConfig(string $style, array $specs = []): array
    {
        return match ($style) {
            self::STYLE_FRAMELESS => [
                'has_stiles' => false,
                'has_rails' => false,
                'has_mid_rails' => false,
                'face_position' => 'flush',      // Faces flush with box edges
                'overlay_amount' => 0,
                'reveal_gap' => $specs['frameless_reveal_gap']
                    ?? ConstructionStandardsService::getFallbackDefault('frameless_reveal_gap', 0.09375),
                'bottom_reveal' => $specs['frameless_bottom_reveal']
                    ?? ConstructionStandardsService::getFallbackDefault('frameless_bottom_reveal', 0),
                'description' => 'European/modern - no face frame, maximizes interior space',
            ],
            self::STYLE_FACE_FRAME => [
                'has_stiles' => true,
                'has_rails' => true,
                'has_mid_rails' => true,
                'face_position' => 'inside',     // Faces fit inside rail openings
                'overlay_amount' => 0,
                'reveal_gap' => $specs['face_frame_reveal_gap']
                    ?? ConstructionStandardsService::getFallbackDefault('face_frame_reveal_gap', 0.125),
                'bottom_reveal' => $specs['face_frame_bottom_reveal']
                    ?? ConstructionStandardsService::getFallbackDefault('face_frame_bottom_reveal', 0.125),
                'description' => 'Traditional American - structural face frame with visible rails',
            ],
            self::STYLE_FULL_OVERLAY => [
                'has_stiles' => true,
                'has_rails' => false,            // Rails optional (covered by faces)
                'has_mid_rails' => false,        // Mid rails not needed (faces cover them)
                'face_position' => 'overlay',    // Faces overlay the stiles
                'overlay_amount' => $specs['full_overlay_amount']
                    ?? ConstructionStandardsService::getFallbackDefault('full_overlay_amount', 1.25),
                'reveal_gap' => $specs['full_overlay_reveal_gap']
                    ?? ConstructionStandardsService::getFallbackDefault('full_overlay_reveal_gap', 0.125),
                'bottom_reveal' => $specs['full_overlay_bottom_reveal']
                    ?? ConstructionStandardsService::getFallbackDefault('full_overlay_bottom_reveal', 0),
                'description' => 'Modern aesthetic with face frame strength - TCS default',
            ],
            self::STYLE_INSET => [
                'has_stiles' => true,
                'has_rails' => true,
                'has_mid_rails' => true,
                'face_position' => 'inset',      // Faces sit flush inside frame openings
                'overlay_amount' => 0,
                'reveal_gap' => $specs['inset_reveal_gap']
                    ?? ConstructionStandardsService::getFallbackDefault('inset_reveal_gap', 0.0625),
                'bottom_reveal' => $specs['inset_bottom_reveal']
                    ?? ConstructionStandardsService::getFallbackDefault('inset_bottom_reveal', 0.0625),
                'description' => 'High-end traditional - requires precision craftsmanship',
            ],
            self::STYLE_PARTIAL_OVERLAY => [
                'has_stiles' => true,
                'has_rails' => true,
                'has_mid_rails' => true,
                'face_position' => 'overlay',    // Faces partially overlay frame
                'overlay_amount' => $specs['partial_overlay_amount']
                    ?? ConstructionStandardsService::getFallbackDefault('partial_overlay_amount', 0.375),
                'reveal_gap' => $specs['partial_overlay_reveal_gap']
                    ?? ConstructionStandardsService::getFallbackDefault('partial_overlay_reveal_gap', 0.125),
                'bottom_reveal' => $specs['partial_overlay_bottom_reveal']
                    ?? ConstructionStandardsService::getFallbackDefault('partial_overlay_bottom_reveal', 0.125),
                'description' => 'Standard/economical option with visible frame',
            ],
            default => throw new \InvalidArgumentException("Unknown face frame style: $style"),
        };
    }

    /**
     * Calculate face dimensions based on face frame style.
     *
     * This method determines the width and X position of drawer/door faces
     * based on the selected face frame style.
     *
     * @param string $style Face frame style
     * @param float $openingWidth Face frame opening width (between stiles)
     * @param float $railXPosition X position where rail starts (inside edge of left stile)
     * @param float $cabinetWidth Full cabinet width
     * @param array $specs Cabinet specs array (from Construction Template)
     * @return array ['face_width' => float, 'face_x' => float]
     */
    public function calculateFaceDimensions(string $style, float $openingWidth, float $railXPosition, float $cabinetWidth, array $specs = []): array
    {
        $config = $this->getFaceFrameConfig($style, $specs);

        return match ($config['face_position']) {
            'flush' => [
                // Frameless: Face equals cabinet width
                'face_width' => $cabinetWidth,
                'face_x' => 0,
            ],
            'inside' => [
                // Traditional face frame: Face fits inside rail opening
                'face_width' => $openingWidth,
                'face_x' => $railXPosition,
            ],
            'overlay' => [
                // Full or partial overlay: Face extends past opening
                'face_width' => $openingWidth + (2 * $config['overlay_amount']),
                'face_x' => $railXPosition - $config['overlay_amount'],
            ],
            'inset' => [
                // Inset: Face smaller than opening by reveal gap
                'face_width' => $openingWidth - (2 * $config['reveal_gap']),
                'face_x' => $railXPosition + $config['reveal_gap'],
            ],
            default => [
                'face_width' => $openingWidth,
                'face_x' => $railXPosition,
            ],
        };
    }

    /**
     * PART CATEGORIES define which boundary constraints apply.
     *
     * EXTERNAL: Parts that can extend beyond cabinet box (face frame, end panels)
     * INTERNAL: Parts confined within cabinet box boundaries
     * BOX: Parts that define the cabinet box itself
     */
    public const CATEGORY_EXTERNAL = 'external';  // Face frame, end panels - can extend beyond box
    public const CATEGORY_INTERNAL = 'internal';  // Stretchers, backings, drawer boxes - confined to box
    public const CATEGORY_BOX = 'box';            // Sides, bottom, back - define the box boundaries

    /**
     * Map part types to their constraint category.
     */
    protected const PART_CATEGORIES = [
        // BOX parts - define cabinet boundaries
        'cabinet_box' => self::CATEGORY_BOX,

        // EXTERNAL parts - can extend beyond box
        'face_frame' => self::CATEGORY_EXTERNAL,
        'finished_end' => self::CATEGORY_EXTERNAL,
        'false_front' => self::CATEGORY_EXTERNAL,  // Face extends beyond stiles (full overlay)
        'drawer_face' => self::CATEGORY_EXTERNAL,  // Face extends beyond stiles (full overlay)

        // INTERNAL parts - must be confined within box
        'stretcher' => self::CATEGORY_INTERNAL,
        'false_front_backing' => self::CATEGORY_INTERNAL,
        'drawer_box' => self::CATEGORY_INTERNAL,
        'shelf' => self::CATEGORY_INTERNAL,
        'divider' => self::CATEGORY_INTERNAL,
    ];

    /**
     * Calculate internal boundary constraints.
     *
     * All INTERNAL parts must fit within these bounds.
     *
     * @param array $specs Cabinet specifications
     * @param array $gate1 Gate 1 outputs (box dimensions)
     * @return array Internal boundary constraints [x_min, x_max, y_min, y_max, z_min, z_max]
     */
    public function calculateInternalBounds(array $specs, array $gate1): array
    {
        $sideThickness = $specs['side_panel_thickness'] ?? self::PLYWOOD_3_4;
        $backThickness = $specs['back_panel_thickness'] ?? self::PLYWOOD_3_4;
        $bottomThickness = $specs['bottom_panel_thickness'] ?? self::PLYWOOD_3_4;
        $cabW = $specs['width'];
        $cabD = $specs['depth'];
        $boxH = $gate1['outputs']['box_height'];

        return [
            'x_min' => $sideThickness,                    // Inside left side
            'x_max' => $cabW - $sideThickness,            // Inside right side
            'y_min' => $bottomThickness,                  // Top of bottom panel
            'y_max' => $boxH,                             // Top of box
            'z_min' => self::PLYWOOD_3_4,                 // Behind face frame (Z=0.75)
            'z_max' => $cabD - $backThickness,            // Front of back panel
            'inside_width' => $gate1['outputs']['inside_width'],
            'inside_depth' => $gate1['outputs']['inside_depth'],
        ];
    }

    /**
     * Validate that a part respects its category constraints.
     *
     * @param array $part Part definition with position, dimensions, part_type
     * @param array $internalBounds Internal boundary constraints
     * @return array ['valid' => bool, 'violations' => [...]]
     */
    public function validatePartConstraints(array $part, array $internalBounds): array
    {
        $partType = $part['part_type'] ?? 'cabinet_box';
        $category = self::PART_CATEGORIES[$partType] ?? self::CATEGORY_BOX;

        // Only INTERNAL parts need constraint validation
        if ($category !== self::CATEGORY_INTERNAL) {
            return ['valid' => true, 'violations' => [], 'category' => $category];
        }

        $pos = $part['position'];
        $dim = $part['dimensions'];
        $violations = [];

        // Check X bounds
        if ($pos['x'] < $internalBounds['x_min']) {
            $violations[] = "X start ({$pos['x']}) < x_min ({$internalBounds['x_min']})";
        }
        if ($pos['x'] + $dim['w'] > $internalBounds['x_max']) {
            $violations[] = "X end (" . ($pos['x'] + $dim['w']) . ") > x_max ({$internalBounds['x_max']})";
        }

        // Check Y bounds
        if ($pos['y'] < $internalBounds['y_min']) {
            $violations[] = "Y start ({$pos['y']}) < y_min ({$internalBounds['y_min']})";
        }
        if ($pos['y'] + $dim['h'] > $internalBounds['y_max']) {
            $violations[] = "Y end (" . ($pos['y'] + $dim['h']) . ") > y_max ({$internalBounds['y_max']})";
        }

        // Check Z bounds
        if ($pos['z'] < $internalBounds['z_min']) {
            $violations[] = "Z start ({$pos['z']}) < z_min ({$internalBounds['z_min']})";
        }
        if ($pos['z'] + $dim['d'] > $internalBounds['z_max']) {
            $violations[] = "Z end (" . ($pos['z'] + $dim['d']) . ") > z_max ({$internalBounds['z_max']})";
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
            'category' => $category,
            'part_name' => $part['part_name'] ?? 'unknown',
        ];
    }

    /**
     * Constrain a part to internal boundaries.
     *
     * Automatically adjusts position and dimensions to fit within bounds.
     *
     * @param array $part Part definition
     * @param array $internalBounds Internal boundary constraints
     * @return array Constrained part definition
     */
    public function constrainToInternalBounds(array $part, array $internalBounds): array
    {
        $partType = $part['part_type'] ?? 'cabinet_box';
        $category = self::PART_CATEGORIES[$partType] ?? self::CATEGORY_BOX;

        // Only constrain INTERNAL parts
        if ($category !== self::CATEGORY_INTERNAL) {
            return $part;
        }

        $pos = $part['position'];
        $dim = $part['dimensions'];

        // Constrain X
        $pos['x'] = max($pos['x'], $internalBounds['x_min']);
        $maxWidth = $internalBounds['x_max'] - $pos['x'];
        $dim['w'] = min($dim['w'], $maxWidth);

        // Constrain Y
        $pos['y'] = max($pos['y'], $internalBounds['y_min']);
        $maxHeight = $internalBounds['y_max'] - $pos['y'];
        $dim['h'] = min($dim['h'], $maxHeight);

        // Constrain Z
        $pos['z'] = max($pos['z'], $internalBounds['z_min']);
        $maxDepth = $internalBounds['z_max'] - $pos['z'];
        $dim['d'] = min($dim['d'], $maxDepth);

        $part['position'] = $pos;
        $part['dimensions'] = $dim;
        $part['_constrained'] = true;

        return $part;
    }

    /**
     * Generate 3D positions for all cabinet parts.
     *
     * @param array $specs Input specifications (from CabinetMathAuditService)
     * @param array $gate1 Gate 1 outputs (box dimensions)
     * @param array $gate4 Gate 4 outputs (drawer clearances)
     * @param array $gate5 Gate 5 outputs (stretchers)
     * @param array $gate6 Gate 6 outputs (face frame)
     * @return array 3D position data with coordinate system metadata
     */
    public function generate3dPositions(array $specs, array $gate1, array $gate4, array $gate5, array $gate6): array
    {
        $cabW = $specs['width'];
        $cabH = $specs['height'];
        $cabD = $specs['depth'];
        $boxH = $gate1['outputs']['box_height'];
        $toeKick = $specs['toe_kick_height'];
        $toeKickRecess = $specs['toe_kick_recess'] ?? 3.0;
        $sideThickness = $specs['side_panel_thickness'];
        $backThickness = $specs['back_panel_thickness'];
        $insideW = $gate1['outputs']['inside_width'];
        $insideD = $gate1['outputs']['inside_depth'];
        $ffStile = $specs['face_frame_stile'];
        $ffRail = $specs['face_frame_rail'];
        $gap = self::COMPONENT_GAP;

        // Get face frame style configuration (TCS default: full_overlay)
        $faceFrameStyle = $specs['face_frame_style'] ?? self::STYLE_FULL_OVERLAY;
        $ffConfig = $this->getFaceFrameConfig($faceFrameStyle, $specs);

        // Calculate internal bounds for INTERNAL parts
        $internalBounds = $this->calculateInternalBounds($specs, $gate1);

        $parts = [];

        // ========================================
        // COORDINATE SYSTEM: Y=0 at BOTTOM of box
        // Y increases upward (Y-up system)
        // Toe kick is in NEGATIVE Y (below box)
        // ========================================

        // ========================================
        // CABINET BOX PARTS
        // Assembly rules from ConstructionStandardsService (via specs)
        // ========================================

        // Get assembly rules from specs (defaults from ConstructionStandardsService)
        $sidesOnBottom = $specs['sides_on_bottom'] ?? true;
        $backInsetFromSides = $specs['back_inset_from_sides'] ?? false;
        $stretchersOnTop = $specs['stretchers_on_top'] ?? true;  // TCS: Stretchers sit ON TOP of sides
        $stretcherThickness = $specs['stretcher_thickness'] ?? self::PLYWOOD_3_4;

        // Calculate dimensions based on assembly rules
        // TCS Standard: Bottom → Sides → Stretchers (stacked vertically)
        if ($sidesOnBottom) {
            // TCS Standard: Sides sit ON TOP of bottom panel
            $sideYPosition = $sideThickness;  // Start above bottom panel
            $bottomWidth = $cabW;              // Full width (sides on top)
            $bottomXPosition = 0;              // Start at X=0

            // Side height depends on whether stretchers sit on top
            if ($stretchersOnTop) {
                // Sides shortened for both bottom AND stretcher
                $sideHeight = $boxH - $sideThickness - $stretcherThickness;
            } else {
                // Sides only shortened for bottom
                $sideHeight = $boxH - $sideThickness;
            }
        } else {
            // Alternative: Sides extend full height, bottom between sides
            $sideHeight = $boxH;
            $sideYPosition = 0;
            $bottomWidth = $insideW;           // Fits between sides
            $bottomXPosition = $sideThickness; // Inset from sides

            if ($stretchersOnTop) {
                $sideHeight = $boxH - $stretcherThickness;
            }
        }

        // Calculate side/bottom depth based on assembly rules
        // TCS Standard: Face frame in FRONT, back panel in BACK
        // Sides and bottom fit BETWEEN face frame and back panel
        $faceFrameThickness = $specs['face_frame_thickness'] ?? self::PLYWOOD_3_4;

        if ($backInsetFromSides) {
            // Back fits BETWEEN sides (inset from edges)
            // Sides go full depth, back sits inside
            $sideDepth = $cabD;                 // Full cabinet depth
            $bottomDepth = $cabD - $backThickness;  // Shortened for back panel
            $backWidth = $insideW;              // Back fits between sides
            $backXPosition = $sideThickness;    // Inset from sides
        } else {
            // TCS Standard: Cabinet depth (18-3/4") = INTERIOR CAVITY depth
            // Back panel sits OUTSIDE/BEHIND, does NOT reduce cavity
            // Sides and bottom go FULL cabinet depth (18-3/4")
            // Back panel attaches to the back, adding to total depth
            $sideDepth = $cabD;                     // Full cavity depth (18.75")
            $bottomDepth = $cabD;                   // Full cavity depth (18.75")
            $backWidth = $cabW;                     // Back spans full width
            $backXPosition = 0;                     // Starts at X=0
        }

        // Z position for sides/bottom starts BEHIND the face frame
        // Face frame sits in FRONT of the cabinet box
        // Sides/bottom align with back of face frame (stile depth)
        // This prevents collision between face frame and cabinet box
        // Default from ConstructionStandardsService (TCS standard: 1" actual from 5/4 hardwood)
        $defaultStileDepth = ConstructionStandardsService::getFallbackDefault('face_frame_thickness', 1.0);
        $stileDepth = $specs['face_frame_stile_depth'] ?? $defaultStileDepth;
        $boxZPosition = $stileDepth;

        // LEFT SIDE PANEL - starts behind face frame
        $parts['left_side'] = [
            'part_name' => 'Left Side',
            'part_type' => 'cabinet_box',
            'position' => ['x' => 0, 'y' => $sideYPosition, 'z' => $boxZPosition],
            'dimensions' => ['w' => $sideThickness, 'h' => $sideHeight, 'd' => $sideDepth],
            'cut_dimensions' => ['width' => $sideDepth, 'length' => $sideHeight, 'thickness' => $sideThickness],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'vertical',
                'face_up' => 'outside',
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => ['front'],
                'machining' => ['shelf_pin_holes', 'runner_mounting'],
            ],
            'material' => '3/4" Plywood',
            'assembly_rule' => $sidesOnBottom ? 'sides_on_bottom' : 'sides_full_height',
        ];

        // RIGHT SIDE PANEL - starts behind face frame
        $parts['right_side'] = [
            'part_name' => 'Right Side',
            'part_type' => 'cabinet_box',
            'position' => ['x' => $cabW - $sideThickness, 'y' => $sideYPosition, 'z' => $boxZPosition],
            'dimensions' => ['w' => $sideThickness, 'h' => $sideHeight, 'd' => $sideDepth],
            'cut_dimensions' => ['width' => $sideDepth, 'length' => $sideHeight, 'thickness' => $sideThickness],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'vertical',
                'face_up' => 'outside',
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => ['front'],
                'machining' => ['shelf_pin_holes', 'runner_mounting'],
            ],
            'material' => '3/4" Plywood',
            'assembly_rule' => $sidesOnBottom ? 'sides_on_bottom' : 'sides_full_height',
        ];

        // BOTTOM PANEL - starts behind face frame
        $parts['bottom'] = [
            'part_name' => 'Bottom',
            'part_type' => 'cabinet_box',
            'position' => ['x' => $bottomXPosition, 'y' => 0, 'z' => $boxZPosition],
            'dimensions' => ['w' => $bottomWidth, 'h' => $sideThickness, 'd' => $bottomDepth],
            'cut_dimensions' => ['width' => $bottomDepth, 'length' => $bottomWidth, 'thickness' => $sideThickness],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'horizontal',
                'face_up' => 'top',
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => ['front'],
                'machining' => [],
            ],
            'material' => '3/4" Plywood',
            'assembly_rule' => $sidesOnBottom ? 'sides_on_bottom' : 'bottom_between_sides',
        ];

        // BACK PANEL - position/width based on assembly rule
        // TCS Standard: Back sits BEHIND sides/bottom (at end of cavity)
        // Back Z = boxZPosition + sideDepth (where sides end)
        $backZPosition = $backInsetFromSides
            ? ($cabD - $backThickness)
            : ($boxZPosition + $sideDepth);  // 1.0 + 18.75 = 19.75
        $parts['back'] = [
            'part_name' => 'Back',
            'part_type' => 'cabinet_box',
            'position' => ['x' => $backXPosition, 'y' => 0, 'z' => $backZPosition],
            'dimensions' => ['w' => $backWidth, 'h' => $boxH, 'd' => $backThickness],
            'cut_dimensions' => ['width' => $backWidth, 'length' => $boxH, 'thickness' => $backThickness],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'vertical',
                'face_up' => 'back',
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => [],
                'machining' => [],
            ],
            'material' => '3/4" Plywood',
        ];

        // TOE KICK - in NEGATIVE Y (below the box)
        // Get end panel settings for toe kick width calculation
        $finishedEndEnabled = $specs['finished_end_enabled'] ?? true;
        $finishedEndGap = $specs['finished_end_gap'] ?? 0.25;
        $endPanelThickness = $sideThickness;  // Typically 0.75"
        $leftEndPanel = $specs['left_end_panel'] ?? false;
        $rightEndPanel = $specs['right_end_panel'] ?? false;

        // Calculate toe kick extensions for end panels
        // TCS Rule: Toe kick extends from inner face of left end panel to inner face of right end panel
        //
        // Without end panels: toe kick goes from side to side (insideW at X = sideThickness)
        // With left end panel: toe kick extends left to -finishedEndGap (inner face of left EP)
        // With right end panel: toe kick extends right to cabW + finishedEndGap (inner face of right EP)
        //
        // End panel position: X = -(finishedEndGap + endPanelThickness)
        // End panel inner face: X = -(finishedEndGap + endPanelThickness) + endPanelThickness = -finishedEndGap

        if ($finishedEndEnabled && $leftEndPanel) {
            // Left toe kick edge at inner face of left end panel
            $toeKickX = -$finishedEndGap;
        } else {
            // Standard: starts at inside of left side panel
            $toeKickX = $sideThickness;
        }

        if ($finishedEndEnabled && $rightEndPanel) {
            // Right toe kick edge at inner face of right end panel
            // Right EP X = cabW + finishedEndGap, so inner face = cabW + finishedEndGap
            $toeKickRightEdge = $cabW + $finishedEndGap;
        } else {
            // Standard: ends at inside of right side panel
            $toeKickRightEdge = $cabW - $sideThickness;
        }

        $toeKickWidth = $toeKickRightEdge - $toeKickX;

        $toeKickNotes = "Recessed {$toeKickRecess}\" from front face. Top of toe kick touches Y=0.";
        if ($leftEndPanel || $rightEndPanel) {
            $toeKickNotes .= ' Extends to end panel inner faces.';
        }

        $parts['toe_kick'] = [
            'part_name' => 'Toe Kick',
            'part_type' => 'toe_kick',  // Changed from cabinet_box for proper layer assignment
            'position' => ['x' => $toeKickX, 'y' => -$toeKick, 'z' => $toeKickRecess],
            'dimensions' => ['w' => $toeKickWidth, 'h' => $toeKick, 'd' => $sideThickness],
            'cut_dimensions' => ['width' => $toeKickWidth, 'length' => $toeKick, 'thickness' => $sideThickness],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'horizontal',
                'face_up' => 'front',
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => [],
                'machining' => [],
            ],
            'material' => '3/4" Plywood',
            'notes' => $toeKickNotes,
        ];

        // ========================================
        // FACE FRAME PARTS
        // Generation controlled by face_frame_style configuration
        // TCS Rule: Stiles extend FULL CABINET HEIGHT (to floor)
        // TCS Rule: If end panels exist, stiles extend to OUTER WIDTH and miter with end panel
        // Rails fit between stiles
        // (End panel settings already loaded above for toe kick calculation)
        // ========================================

        // Calculate face frame extension for end panels
        // Stiles extend past cabinet to cover end panels (mitered joint)
        $leftExtension = ($finishedEndEnabled && $leftEndPanel) ? ($finishedEndGap + $endPanelThickness) : 0;
        $rightExtension = ($finishedEndEnabled && $rightEndPanel) ? ($finishedEndGap + $endPanelThickness) : 0;

        // Total face frame width (cabinet + extensions for end panels)
        $faceFrameWidth = $cabW + $leftExtension + $rightExtension;

        // Stile positions account for end panel extensions
        $leftStileX = -$leftExtension;  // Extends left if end panel exists
        $rightStileX = $cabW + $rightExtension - $ffStile;  // Extends right if end panel exists

        // Calculate opening width and rail position based on style
        // For frameless: opening = cabinet width, no stiles
        // For styles with stiles: opening = between stiles
        if ($ffConfig['has_stiles']) {
            $openingW = $faceFrameWidth - (2 * $ffStile);
            $railXPosition = $leftStileX + $ffStile;
        } else {
            // Frameless: full cabinet width, no stile offset
            $openingW = $cabW;
            $railXPosition = 0;
        }

        // STILES - only if style has them
        if ($ffConfig['has_stiles']) {
            // LEFT STILE - FULL HEIGHT from floor to top
            // If left end panel: extends to outer edge, mitered with end panel
            $stileHeight = $cabH;  // Full cabinet height including toe kick
            $stileYPosition = -$toeKick;  // Start at floor level (negative Y)

            $leftStileMiter = ($finishedEndEnabled && $leftEndPanel) ? 'outside_miter' : 'none';
            $parts['left_stile'] = [
                'part_name' => 'Left Stile',
                'part_type' => 'face_frame',
                'position' => ['x' => $leftStileX, 'y' => $stileYPosition, 'z' => 0],
                'dimensions' => ['w' => $ffStile, 'h' => $stileHeight, 'd' => $faceFrameThickness],
                'cut_dimensions' => ['width' => $ffStile, 'length' => $stileHeight, 'thickness' => $faceFrameThickness],
                'orientation' => [
                    'rotation' => 0,
                    'grain_direction' => 'vertical',
                    'face_up' => 'front',
                ],
                'cnc' => [
                    'finished_ends' => 'both',
                    'edgebanding' => ['front', 'back'],
                    'machining' => [],
                    'miter' => $leftStileMiter,
                ],
                'material' => '5/4 Hardwood',
                'notes' => $leftEndPanel
                    ? 'TCS Rule: Stile extends to outer edge, mitered with end panel'
                    : 'TCS Rule: Stiles extend to floor',
                'end_panel_extension' => $leftExtension,
                'face_frame_style' => $faceFrameStyle,
            ];

            // RIGHT STILE - FULL HEIGHT from floor to top
            // If right end panel: extends to outer edge, mitered with end panel
            $rightStileMiter = ($finishedEndEnabled && $rightEndPanel) ? 'outside_miter' : 'none';
            $parts['right_stile'] = [
                'part_name' => 'Right Stile',
                'part_type' => 'face_frame',
                'position' => ['x' => $rightStileX, 'y' => $stileYPosition, 'z' => 0],
                'dimensions' => ['w' => $ffStile, 'h' => $stileHeight, 'd' => $faceFrameThickness],
                'cut_dimensions' => ['width' => $ffStile, 'length' => $stileHeight, 'thickness' => $faceFrameThickness],
                'orientation' => [
                    'rotation' => 0,
                    'grain_direction' => 'vertical',
                    'face_up' => 'front',
                ],
                'cnc' => [
                    'finished_ends' => 'both',
                    'edgebanding' => ['front', 'back'],
                    'machining' => [],
                    'miter' => $rightStileMiter,
                ],
                'material' => '5/4 Hardwood',
                'notes' => $rightEndPanel
                    ? 'TCS Rule: Stile extends to outer edge, mitered with end panel'
                    : 'TCS Rule: Stiles extend to floor',
                'end_panel_extension' => $rightExtension,
                'face_frame_style' => $faceFrameStyle,
            ];
        }

        // TOP RAIL - only if style has rails
        if ($ffConfig['has_rails']) {
            // TCS Rule: Top rail aligns with TOP of stretcher (top of box)
            // The top rail sits behind the stretcher, both tops at same Y level
            $topRailYTop = $boxH;  // Top of box = top of stretcher
            $topRailYBottom = $topRailYTop - $ffRail;

            $parts['top_rail'] = [
                'part_name' => 'Top Rail',
                'part_type' => 'face_frame',
                'position' => ['x' => $railXPosition, 'y' => $topRailYBottom, 'z' => 0],
                'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => $faceFrameThickness],
                'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => $faceFrameThickness],
                'orientation' => [
                    'rotation' => 0,
                    'grain_direction' => 'horizontal',
                    'face_up' => 'front',
                ],
                'cnc' => [
                    'finished_ends' => 'none',
                    'edgebanding' => ['front', 'back'],
                    'machining' => [],
                ],
                'material' => '3/4" Hardwood',
                'face_frame_style' => $faceFrameStyle,
            ];

            // BOTTOM RAIL - at bottom of box (Y = 0)
            $parts['bottom_rail'] = [
                'part_name' => 'Bottom Rail',
                'part_type' => 'face_frame',
                'position' => ['x' => $railXPosition, 'y' => 0, 'z' => 0],
                'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => $faceFrameThickness],
                'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => $faceFrameThickness],
                'orientation' => [
                    'rotation' => 0,
                    'grain_direction' => 'horizontal',
                    'face_up' => 'front',
                ],
                'cnc' => [
                    'finished_ends' => 'none',
                    'edgebanding' => ['front', 'back'],
                    'machining' => [],
                ],
                'material' => '3/4" Hardwood',
                'face_frame_style' => $faceFrameStyle,
            ];
        } // End of has_rails conditional

        // ========================================
        // COMPONENT POSITIONS (False Fronts, Drawers)
        // Face positioning controlled by face_frame_style configuration
        // Stack from TOP going DOWN in Y-up system
        // Top of box = boxH, bottom = 0
        // ========================================

        // Calculate face dimensions based on face frame style
        $faceDims = $this->calculateFaceDimensions($faceFrameStyle, $openingW, $railXPosition, $cabW, $specs);
        $faceWidth = $faceDims['face_width'];
        $faceX = $faceDims['face_x'];

        $currentY = $boxH - $gap; // Start at top (just below top of box)
        $falseFronts = $specs['false_fronts'] ?? [];
        $drawerHeights = $specs['drawer_heights'] ?? [];

        // False fronts (at top)
        foreach ($falseFronts as $idx => $ff) {
            $ffNum = $idx + 1;
            $faceH = $ff['face_height'] ?? 7;

            // FALSE FRONT FACE - position is at bottom of face
            // Position and width from calculateFaceDimensions based on style
            $faceY = $currentY - $faceH;
            $parts["false_front_{$ffNum}_face"] = [
                'part_name' => "False Front #{$ffNum} Face",
                'part_type' => 'false_front',
                'position' => ['x' => $faceX, 'y' => $faceY, 'z' => 0],
                'dimensions' => ['w' => $faceWidth, 'h' => $faceH, 'd' => self::PLYWOOD_3_4],
                'cut_dimensions' => ['width' => $faceH, 'length' => $faceWidth, 'thickness' => self::PLYWOOD_3_4],
                'orientation' => [
                    'rotation' => 0,
                    'grain_direction' => 'horizontal',
                    'face_up' => 'front',
                ],
                'cnc' => [
                    'finished_ends' => 'both',
                    'edgebanding' => ['top', 'bottom'],
                    'machining' => [],
                ],
                'material' => '3/4" Plywood',
                'notes' => "Face frame style: {$faceFrameStyle} - {$ffConfig['description']}",
                'face_frame_style' => $faceFrameStyle,
            ];

            // FALSE FRONT BACKING (horizontal stretcher)
            // INTERNAL part - must respect internal bounds and stretcher collision
            if ($ff['has_backing'] ?? true) {
                $backingH = $ff['backing_height'] ?? 3;

                // Calculate initial centered position
                $backingYCentered = $faceY + ($faceH - $backingH) / 2;

                // CONSTRAINT: Top of backing must be at or below stretcher bottom
                // Stretcher is at Y=28 (bottom) to Y=28.75 (top)
                // So backing top must be <= 28 (stretcherY)
                $stretcherY = $stretchersOnTop ? ($boxH - $stretcherThickness) : $boxH;
                $backingTopMax = $stretcherY;  // Must not exceed this

                // Calculate final Y position respecting constraint
                $backingYTop = $backingYCentered + $backingH;
                if ($backingYTop > $backingTopMax) {
                    // Shift backing down to respect stretcher
                    $backingY = $backingTopMax - $backingH;
                } else {
                    $backingY = $backingYCentered;
                }

                // Also ensure backing doesn't go below bottom panel
                $backingY = max($backingY, $internalBounds['y_min']);

                // False front backing sits INSIDE the cabinet cavity, behind face frame
                // Z position = $boxZPosition (behind face frame, same as sides/bottom)
                $backingPart = [
                    'part_name' => "False Front #{$ffNum} Backing",
                    'part_type' => 'false_front_backing',
                    'position' => ['x' => $sideThickness, 'y' => $backingY, 'z' => $boxZPosition],
                    'dimensions' => ['w' => $insideW, 'h' => $backingH, 'd' => self::PLYWOOD_3_4],
                    'cut_dimensions' => ['width' => $backingH, 'length' => $insideW, 'thickness' => self::PLYWOOD_3_4],
                    'orientation' => [
                        'rotation' => 90,
                        'grain_direction' => 'horizontal',
                        'face_up' => 'top',
                    ],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => [],
                        'machining' => [],
                    ],
                    'material' => '3/4" Plywood',
                    'notes' => 'Laid FLAT - doubles as stretcher (TCS rule)',
                    'is_stretcher' => true,
                    '_constraint_applied' => $backingYTop > $backingTopMax ? 'shifted_down_for_stretcher' : 'none',
                ];

                // Validate against internal bounds
                $validation = $this->validatePartConstraints($backingPart, $internalBounds);
                $backingPart['_constraint_validation'] = $validation;

                $parts["false_front_{$ffNum}_backing"] = $backingPart;
            }

            $currentY = $faceY - $gap; // Move down for next component

            // Mid rail after false front - only if style has mid rails
            if ($ffConfig['has_mid_rails']) {
                $midRailY = $faceY - $gap / 2 - $ffRail / 2;
                $parts["mid_rail_ff_{$ffNum}"] = [
                    'part_name' => "Mid Rail (after FF#{$ffNum})",
                    'part_type' => 'face_frame',
                    'position' => ['x' => $railXPosition, 'y' => $midRailY, 'z' => 0],
                    'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => $faceFrameThickness],
                    'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => $faceFrameThickness],
                    'orientation' => [
                        'rotation' => 0,
                        'grain_direction' => 'horizontal',
                        'face_up' => 'front',
                    ],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => ['front', 'back'],
                        'machining' => [],
                    ],
                    'material' => '3/4" Hardwood',
                    'face_frame_style' => $faceFrameStyle,
                ];
            }
        }

        // Drawers (from top to bottom in Y-up system)
        $drawerBoxes = $gate4['drawer_boxes'] ?? [];
        foreach ($drawerHeights as $idx => $drawerFaceH) {
            $drawerNum = $idx + 1;
            $drawerType = ($idx === 0 && !empty($falseFronts)) ? 'u_drawer' : 'drawer';
            $drawerLabel = $drawerType === 'u_drawer' ? 'U-Shaped Drawer' : 'Drawer';

            // DRAWER FACE - position is at bottom of face
            $faceY = $currentY - $drawerFaceH;

            // Bottom reveal depends on face frame style:
            // Now pulled from template via getFaceFrameConfig()
            // - full_overlay/frameless: No bottom reveal (default 0)
            // - face_frame/inset/partial_overlay: 1/8" reveal (default 0.125)
            $isBottomDrawer = ($idx === count($drawerHeights) - 1);
            if ($isBottomDrawer) {
                // Use bottom_reveal from face frame config (template-configurable)
                $bottomReveal = $ffConfig['bottom_reveal'] ?? 0;
                if ($faceY < $bottomReveal) {
                    $faceY = $bottomReveal;
                }
            }

            // Drawer face front aligns with front of face frame (Z=0)
            // Thickness from specs or TCS standard 1"
            // Position and width from calculateFaceDimensions based on style
            $drawerFaceThickness = $specs['drawer_face_thickness'] ?? self::DRAWER_FACE_THICKNESS;
            $parts["drawer_{$drawerNum}_face"] = [
                'part_name' => "{$drawerLabel} #{$drawerNum} Face",
                'part_type' => 'drawer_face',
                'position' => ['x' => $faceX, 'y' => $faceY, 'z' => 0],
                'dimensions' => ['w' => $faceWidth, 'h' => $drawerFaceH, 'd' => $drawerFaceThickness],
                'cut_dimensions' => ['width' => $drawerFaceH, 'length' => $faceWidth, 'thickness' => $drawerFaceThickness],
                'orientation' => [
                    'rotation' => 0,
                    'grain_direction' => 'horizontal',
                    'face_up' => 'front',
                ],
                'cnc' => [
                    'finished_ends' => 'both',
                    'edgebanding' => ['top', 'bottom'],
                    'machining' => $drawerType === 'u_drawer' ? ['u_cutout'] : [],
                ],
                'material' => '3/4" Plywood',
                'drawer_type' => $drawerType,
                'face_frame_style' => $faceFrameStyle,
                'notes' => "Face frame style: {$faceFrameStyle} - {$ffConfig['description']}",
            ];

            // DRAWER BOX (from Gate 4 calculations) - EXPLODED INTO INDIVIDUAL PARTS
            if (isset($drawerBoxes[$idx])) {
                $box = $drawerBoxes[$idx];
                $boxW = $box['outputs']['box_width'] ?? 0;
                $boxHt = $box['outputs']['box_height_shop'] ?? $box['outputs']['box_height_exact'] ?? 0;
                // Use slide length (box_depth) for position, not shop depth
                // Shop depth includes 1/4" dado extension but drawer movement is slide length
                $boxD = $box['outputs']['box_depth'] ?? 18;

                // Drawer box sits at bottom of opening + bottom clearance
                $drawerBoxY = $faceY + self::BLUM_BOTTOM_CLEARANCE;

                // Drawer box X position: centered on cabinet (aligned with drawer face center)
                // Box attaches to rear of drawer face, so they must share the same center X
                $drawerBoxX = ($cabW - $boxW) / 2;

                // Drawer box Z position: starts at back of drawer FACE
                // Drawer face is at Z=0, thickness from material ($drawerFaceThickness)
                // Drawer box attaches directly to back of drawer face
                // This allows the face to be screwed to box front from inside
                $drawerBoxZ = $drawerFaceThickness;  // Right behind drawer face

                // Drawer box material thicknesses - use DrawerConfiguratorService constants
                $sideThk = DrawerConfiguratorService::MATERIAL_THICKNESS;  // 0.5"
                $bottomThk = DrawerConfiguratorService::BOTTOM_THICKNESS;  // 0.25"
                $dadoDepth = DrawerConfiguratorService::DADO_DEPTH;        // 0.25" - bottom sits in dado
                $dadoInset = DrawerConfiguratorService::BOTTOM_DADO_HEIGHT; // 0.5" - dado is up from bottom
                $dadoClearance = DrawerConfiguratorService::BOTTOM_CLEARANCE_IN_DADO; // 0.0625"

                // Calculate individual part dimensions
                // Sides: full depth, full height, 1/2" thick
                $sideW = $sideThk;
                $sideH = $boxHt;
                $sideD = $boxD;

                // Front/Back: between sides, full height, 1/2" thick
                $fbW = $boxW - (2 * $sideThk);
                $fbH = $boxHt;
                $fbD = $sideThk;

                // Bottom: fits in dado, slightly smaller for clearance
                // Uses dadoClearance from DrawerConfiguratorService::BOTTOM_CLEARANCE_IN_DADO
                $bottomW = $boxW - ($dadoClearance * 2);  // 1/16" clearance each side (0.0625 * 2 = 0.125)
                $bottomH = $bottomThk;
                $bottomD = $boxD - ($dadoInset * 2);      // Dado inset clearance front and back (0.5 * 2 = 1.0)

                // LEFT SIDE - at left edge of drawer box
                $parts["drawer_{$drawerNum}_box_left_side"] = [
                    'part_name' => "{$drawerLabel} #{$drawerNum} Box Left Side",
                    'part_type' => 'drawer_box_side',
                    'position' => [
                        'x' => $drawerBoxX,
                        'y' => $drawerBoxY,
                        'z' => $drawerBoxZ,
                    ],
                    'dimensions' => ['w' => $sideW, 'h' => $sideH, 'd' => $sideD],
                    'cut_dimensions' => ['width' => $sideH, 'length' => $sideD, 'thickness' => $sideThk],
                    'orientation' => ['rotation' => 0, 'grain_direction' => 'horizontal', 'face_up' => 'outside'],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => ['top'],
                        'machining' => ['dado_1_4_for_bottom', 'locking_device_holes'],
                    ],
                    'material' => '1/2" Plywood',
                ];

                // RIGHT SIDE - at right edge of drawer box
                $parts["drawer_{$drawerNum}_box_right_side"] = [
                    'part_name' => "{$drawerLabel} #{$drawerNum} Box Right Side",
                    'part_type' => 'drawer_box_side',
                    'position' => [
                        'x' => $drawerBoxX + $boxW - $sideThk,
                        'y' => $drawerBoxY,
                        'z' => $drawerBoxZ,
                    ],
                    'dimensions' => ['w' => $sideW, 'h' => $sideH, 'd' => $sideD],
                    'cut_dimensions' => ['width' => $sideH, 'length' => $sideD, 'thickness' => $sideThk],
                    'orientation' => ['rotation' => 0, 'grain_direction' => 'horizontal', 'face_up' => 'outside'],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => ['top'],
                        'machining' => ['dado_1_4_for_bottom', 'locking_device_holes'],
                    ],
                    'material' => '1/2" Plywood',
                ];

                // FRONT - between sides, at front (Z=0)
                $parts["drawer_{$drawerNum}_box_front"] = [
                    'part_name' => "{$drawerLabel} #{$drawerNum} Box Front",
                    'part_type' => 'drawer_box_front',
                    'position' => [
                        'x' => $drawerBoxX + $sideThk,
                        'y' => $drawerBoxY,
                        'z' => $drawerBoxZ,
                    ],
                    'dimensions' => ['w' => $fbW, 'h' => $fbH, 'd' => $fbD],
                    'cut_dimensions' => ['width' => $fbH, 'length' => $fbW, 'thickness' => $fbD],
                    'orientation' => ['rotation' => 0, 'grain_direction' => 'horizontal', 'face_up' => 'front'],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => ['top'],
                        'machining' => ['dado_1_4_for_bottom', 'rear_hook_holes'],
                    ],
                    'material' => '1/2" Plywood',
                ];

                // BACK - between sides, at back (Z = boxD - thickness)
                $parts["drawer_{$drawerNum}_box_back"] = [
                    'part_name' => "{$drawerLabel} #{$drawerNum} Box Back",
                    'part_type' => 'drawer_box_back',
                    'position' => [
                        'x' => $drawerBoxX + $sideThk,
                        'y' => $drawerBoxY,
                        'z' => $drawerBoxZ + $boxD - $fbD,
                    ],
                    'dimensions' => ['w' => $fbW, 'h' => $fbH, 'd' => $fbD],
                    'cut_dimensions' => ['width' => $fbH, 'length' => $fbW, 'thickness' => $fbD],
                    'orientation' => ['rotation' => 0, 'grain_direction' => 'horizontal', 'face_up' => 'back'],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => ['top'],
                        'machining' => ['dado_1_4_for_bottom'],
                    ],
                    'material' => '1/2" Plywood',
                ];

                // BOTTOM - sits in dado, raised from bottom of sides
                // Position offsets use DrawerConfiguratorService constants
                $parts["drawer_{$drawerNum}_box_bottom"] = [
                    'part_name' => "{$drawerLabel} #{$drawerNum} Box Bottom",
                    'part_type' => 'drawer_box_bottom',
                    'position' => [
                        'x' => $drawerBoxX + $dadoClearance,     // Use constant (0.0625")
                        'y' => $drawerBoxY + $dadoInset,         // Sits in dado, raised from bottom
                        'z' => $drawerBoxZ + $dadoDepth,         // Use constant (0.25") inset from front
                    ],
                    'dimensions' => ['w' => $bottomW, 'h' => $bottomH, 'd' => $bottomD],
                    'cut_dimensions' => ['width' => $bottomD, 'length' => $bottomW, 'thickness' => $bottomThk],
                    'orientation' => ['rotation' => 90, 'grain_direction' => 'lengthwise', 'face_up' => 'top'],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => [],
                        'machining' => [],
                    ],
                    'material' => '1/4" Plywood',
                ];
            }

            $currentY = $faceY - $gap; // Move down for next component

            // Mid rail between drawers - only if style has mid rails (not after last one)
            if ($ffConfig['has_mid_rails'] && $idx < count($drawerHeights) - 1) {
                $midRailY = $faceY - $gap / 2 - $ffRail / 2;
                $parts["mid_rail_drawer_{$drawerNum}"] = [
                    'part_name' => "Mid Rail (after Drawer #{$drawerNum})",
                    'part_type' => 'face_frame',
                    'position' => ['x' => $railXPosition, 'y' => $midRailY, 'z' => 0],
                    'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => $faceFrameThickness],
                    'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => $faceFrameThickness],
                    'orientation' => [
                        'rotation' => 0,
                        'grain_direction' => 'horizontal',
                        'face_up' => 'front',
                    ],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => ['front', 'back'],
                        'machining' => [],
                    ],
                    'material' => '3/4" Hardwood',
                    'face_frame_style' => $faceFrameStyle,
                ];
            }
        }

        // ========================================
        // STRETCHERS (excluding false front backings)
        // Width depends on assembly rule:
        // - stretchers_on_top = true: FULL cabinet width (sits on top of sides)
        // - stretchers_on_top = false: Inside width (fits between sides)
        // ========================================
        $stretcherDepth = $gate5['outputs']['stretcher_depth'] ?? 3.0;
        $stretcherThickness = $gate5['outputs']['stretcher_thickness'] ?? self::PLYWOOD_3_4;

        // Stretcher width based on assembly rule
        $stretcherWidth = $stretchersOnTop ? $cabW : $insideW;
        $stretcherXPosition = $stretchersOnTop ? 0 : $sideThickness;

        // Front stretcher (at top of box, BUTTS face frame - no gap)
        $faceFrameThickness = $specs['face_frame_thickness'] ?? self::PLYWOOD_3_4;
        $parts['front_stretcher'] = [
            'part_name' => 'Front Stretcher',
            'part_type' => 'stretcher',
            'position' => ['x' => $stretcherXPosition, 'y' => $boxH - $stretcherThickness, 'z' => $faceFrameThickness],
            'dimensions' => ['w' => $stretcherWidth, 'h' => $stretcherThickness, 'd' => $stretcherDepth],
            'cut_dimensions' => ['width' => $stretcherDepth, 'length' => $stretcherWidth, 'thickness' => $stretcherThickness],
            'orientation' => [
                'rotation' => 90,
                'grain_direction' => 'horizontal',
                'face_up' => 'top',
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => [],
                'machining' => [],
            ],
            'material' => '3/4" Plywood',
        ];

        // Back stretcher (at top of box, BUTTS back panel - no gap)
        // Z position: Back panel starts at $backZPosition, stretcher ends there
        // So stretcher starts at ($backZPosition - stretcherDepth)
        $backStretcherZ = $backZPosition - $stretcherDepth;  // 19.75 - 3 = 16.75
        $parts['back_stretcher'] = [
            'part_name' => 'Back Stretcher',
            'part_type' => 'stretcher',
            'position' => ['x' => $stretcherXPosition, 'y' => $boxH - $stretcherThickness, 'z' => $backStretcherZ],
            'dimensions' => ['w' => $stretcherWidth, 'h' => $stretcherThickness, 'd' => $stretcherDepth],
            'cut_dimensions' => ['width' => $stretcherDepth, 'length' => $stretcherWidth, 'thickness' => $stretcherThickness],
            'orientation' => [
                'rotation' => 90,
                'grain_direction' => 'horizontal',
                'face_up' => 'top',
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => [],
                'machining' => [],
            ],
            'material' => '3/4" Plywood',
        ];

        // ========================================
        // FINISHED END PANELS (for exposed sides)
        // TCS Rule: End panels attach to exposed cabinet sides
        // - Gap between cabinet side and end panel (configurable)
        // - 1/2" extra depth toward wall for scribe/unevenness
        // - Toggleable option (finished_end_enabled)
        // ========================================

        $finishedEndEnabled = $specs['finished_end_enabled'] ?? true;
        $finishedEndGap = $specs['finished_end_gap'] ?? 0.25;        // 1/4" gap between cabinet and panel
        $finishedEndWallExtension = $specs['finished_end_wall_extension'] ?? 0.5;  // 1/2" extra for wall

        // Check which sides need end panels (exposed sides)
        $leftEndPanel = $specs['left_end_panel'] ?? false;
        $rightEndPanel = $specs['right_end_panel'] ?? false;

        if ($finishedEndEnabled && ($leftEndPanel || $rightEndPanel)) {
            $endPanelThickness = $sideThickness;  // Match cabinet side thickness

            // End panel dimensions:
            // - Height: Full cabinet height (stiles to floor)
            // - Depth: Cabinet depth + wall extension (1/2" extra for scribe)
            // - Thickness: Same as cabinet side (typically 3/4")
            $endPanelHeight = $cabH;  // Full height to floor
            $endPanelDepth = $cabD + $finishedEndWallExtension;  // Extra 1/2" toward wall

            // LEFT END PANEL
            if ($leftEndPanel) {
                // Position: Outside of cabinet side, with gap
                // X = -gap - thickness (sits to the left of cabinet)
                // Y = -toeKick (starts at floor)
                // Z = 0 (flush with front, extends past back)
                $parts['left_end_panel'] = [
                    'part_name' => 'Left End Panel',
                    'part_type' => 'finished_end',
                    'position' => [
                        'x' => -$finishedEndGap - $endPanelThickness,
                        'y' => -$toeKick,  // Floor level
                        'z' => 0,          // Flush with face frame front
                    ],
                    'dimensions' => [
                        'w' => $endPanelThickness,
                        'h' => $endPanelHeight,
                        'd' => $endPanelDepth,
                    ],
                    'cut_dimensions' => [
                        'width' => $endPanelDepth,
                        'length' => $endPanelHeight,
                        'thickness' => $endPanelThickness,
                    ],
                    'orientation' => [
                        'rotation' => 0,
                        'grain_direction' => 'vertical',
                        'face_up' => 'outside',
                    ],
                    'cnc' => [
                        'finished_ends' => 'outside',
                        'edgebanding' => ['front', 'bottom'],
                        'machining' => [],
                    ],
                    'material' => '3/4" Finished Plywood',
                    'notes' => "Gap: {$finishedEndGap}\", Wall extension: {$finishedEndWallExtension}\" for scribe",
                    'assembly' => [
                        'gap_from_cabinet' => $finishedEndGap,
                        'wall_extension' => $finishedEndWallExtension,
                    ],
                ];
            }

            // RIGHT END PANEL
            if ($rightEndPanel) {
                // Position: Outside of cabinet side, with gap
                // X = cabW + gap (sits to the right of cabinet)
                // Y = -toeKick (starts at floor)
                // Z = 0 (flush with front, extends past back)
                $parts['right_end_panel'] = [
                    'part_name' => 'Right End Panel',
                    'part_type' => 'finished_end',
                    'position' => [
                        'x' => $cabW + $finishedEndGap,
                        'y' => -$toeKick,  // Floor level
                        'z' => 0,          // Flush with face frame front
                    ],
                    'dimensions' => [
                        'w' => $endPanelThickness,
                        'h' => $endPanelHeight,
                        'd' => $endPanelDepth,
                    ],
                    'cut_dimensions' => [
                        'width' => $endPanelDepth,
                        'length' => $endPanelHeight,
                        'thickness' => $endPanelThickness,
                    ],
                    'orientation' => [
                        'rotation' => 0,
                        'grain_direction' => 'vertical',
                        'face_up' => 'outside',
                    ],
                    'cnc' => [
                        'finished_ends' => 'outside',
                        'edgebanding' => ['front', 'bottom'],
                        'machining' => [],
                    ],
                    'material' => '3/4" Finished Plywood',
                    'notes' => "Gap: {$finishedEndGap}\", Wall extension: {$finishedEndWallExtension}\" for scribe",
                    'assembly' => [
                        'gap_from_cabinet' => $finishedEndGap,
                        'wall_extension' => $finishedEndWallExtension,
                    ],
                ];
            }
        }

        return [
            'coordinate_system' => [
                'origin' => 'Front-Bottom-Left corner of cabinet BOX',
                'x_axis' => 'Left → Right (positive)',
                'y_axis' => 'Bottom → Top (positive) - Y=0 is BOTTOM of box',
                'z_axis' => 'Front → Back (positive)',
                'units' => 'inches',
                'notes' => [
                    'Toe kick is in NEGATIVE Y (below the box)',
                    'Position values are for bottom-left-front corner of each part',
                    'Bottom of bottom panel = Y=0 (the reference plane)',
                ],
            ],
            'cabinet_envelope' => [
                'width' => $cabW,
                'height' => $cabH,
                'box_height' => $boxH,
                'depth' => $cabD,
                'toe_kick_height' => $toeKick,
            ],
            'face_frame_style' => [
                'style' => $faceFrameStyle,
                'config' => $ffConfig,
                'face_dimensions' => [
                    'width' => $faceWidth,
                    'x_position' => $faceX,
                ],
            ],
            'parts' => $parts,
            'part_count' => count($parts),
        ];
    }

    /**
     * Calculate miter joints between parts.
     *
     * Strategy:
     * 1. Identify collision zones between parts
     * 2. Calculate 45° miter cut for each part
     * 3. Each part loses a triangular prism from the collision zone
     *
     * @param array $positions Output from generate3dPositions()
     * @param array $specs Original input specs
     * @return array Parts with miter_cut data added
     */
    public function calculateMiterJoints(array $positions, array $specs): array
    {
        $jointType = $specs['joint_type'] ?? 'butt';

        if ($jointType !== 'miter') {
            return $positions;
        }

        $jointCalc = $this->getJointCalculator();

        // Define part pairs that should have miter joints
        $miterPairs = [];

        // Left end panel + left stile
        if (isset($positions['parts']['left_end_panel']) && isset($positions['parts']['left_stile'])) {
            $miterPairs[] = ['a' => 'left_end_panel', 'b' => 'left_stile', 'corner' => 'left'];
        }

        // Right end panel + right stile
        if (isset($positions['parts']['right_end_panel']) && isset($positions['parts']['right_stile'])) {
            $miterPairs[] = ['a' => 'right_end_panel', 'b' => 'right_stile', 'corner' => 'right'];
        }

        // Apply miter joints using the service
        $jointCalc->applyMiterJoints($positions, $miterPairs);

        return $positions;
    }

    /**
     * Universal miter joint calculator for any two colliding parts.
     *
     * Works for any corner where two parts overlap in the XZ plane.
     * Automatically determines the diagonal direction based on which
     * part is "front" (smaller Z centroid) vs "back" (larger Z centroid).
     *
     * ALGORITHM:
     * 1. Find collision zone (intersection of both parts in XZ)
     * 2. Determine outer corner based on cabinet side (left/right)
     * 3. Front part removes back triangle, back part removes front triangle
     * 4. Each part keeps its mitered edge
     *
     * @param array $partA First part with position/dimensions
     * @param array $partB Second part with position/dimensions
     * @param string $corner Corner type: 'left', 'right', 'front', 'back'
     * @return array ['part_a' => miter_cut|null, 'part_b' => miter_cut|null]
     */
    public function calculateUniversalMiter(array $partA, array $partB, string $corner = 'auto'): array
    {
        $posA = $partA['position'];
        $dimA = $partA['dimensions'];
        $posB = $partB['position'];
        $dimB = $partB['dimensions'];

        // Calculate collision zone bounds in XZ plane
        $collisionXMin = max($posA['x'], $posB['x']);
        $collisionXMax = min($posA['x'] + $dimA['w'], $posB['x'] + $dimB['w']);
        $collisionZMin = max($posA['z'], $posB['z']);
        $collisionZMax = min($posA['z'] + $dimA['d'], $posB['z'] + $dimB['d']);

        // No collision = no miter needed
        if ($collisionXMin >= $collisionXMax || $collisionZMin >= $collisionZMax) {
            return ['part_a' => null, 'part_b' => null];
        }

        // Determine which part is "front" (smaller average Z) vs "back" (larger average Z)
        $centroidZA = $posA['z'] + ($dimA['d'] / 2);
        $centroidZB = $posB['z'] + ($dimB['d'] / 2);
        $frontPart = $centroidZA < $centroidZB ? 'A' : 'B';

        // Auto-detect corner based on X positions if not specified
        if ($corner === 'auto') {
            $centroidXA = $posA['x'] + ($dimA['w'] / 2);
            $centroidXB = $posB['x'] + ($dimB['w'] / 2);
            $avgCentroidX = ($centroidXA + $centroidXB) / 2;
            // If collision is on left side of parts, it's a left corner
            $corner = $avgCentroidX < ($collisionXMin + $collisionXMax) / 2 ? 'right' : 'left';
        }

        // Determine diagonal direction based on corner
        // Left corner: diagonal from (XMin, ZMin) to (XMax, ZMax)
        // Right corner: diagonal from (XMax, ZMin) to (XMin, ZMax)
        if ($corner === 'left') {
            $outerFront = ['x' => $collisionXMin, 'z' => $collisionZMin];
            $innerBack = ['x' => $collisionXMax, 'z' => $collisionZMax];
            $outerBack = ['x' => $collisionXMin, 'z' => $collisionZMax];
            $innerFront = ['x' => $collisionXMax, 'z' => $collisionZMin];
        } else {
            // Right corner (mirror)
            $outerFront = ['x' => $collisionXMax, 'z' => $collisionZMin];
            $innerBack = ['x' => $collisionXMin, 'z' => $collisionZMax];
            $outerBack = ['x' => $collisionXMax, 'z' => $collisionZMax];
            $innerFront = ['x' => $collisionXMin, 'z' => $collisionZMin];
        }

        // Front triangle (Z- side of diagonal): outerFront, innerBack, innerFront
        $frontTriangle = [$outerFront, $innerBack, $innerFront];

        // Back triangle (Z+ side of diagonal): outerFront, innerBack, outerBack
        $backTriangle = [$outerFront, $innerBack, $outerBack];

        // Front part removes back triangle (takes over back territory with miter)
        // Back part removes front triangle (takes over front territory with miter)
        if ($frontPart === 'A') {
            $miterA = $this->buildMiterCut($backTriangle, $posA['y'], $posA['y'] + $dimA['h'], 'back_territory');
            $miterB = $this->buildMiterCut($frontTriangle, $posB['y'], $posB['y'] + $dimB['h'], 'front_territory');
        } else {
            $miterA = $this->buildMiterCut($frontTriangle, $posA['y'], $posA['y'] + $dimA['h'], 'front_territory');
            $miterB = $this->buildMiterCut($backTriangle, $posB['y'], $posB['y'] + $dimB['h'], 'back_territory');
        }

        return [
            'part_a' => $miterA,
            'part_b' => $miterB,
        ];
    }

    /**
     * Build a miter cut definition from triangle vertices.
     *
     * @param array $triangleXZ Array of 3 points [['x'=>, 'z'=>], ...]
     * @param float $yStart Bottom Y of the cut
     * @param float $yEnd Top Y of the cut
     * @param string $territory Label for what territory this removes
     * @return array Miter cut definition
     */
    protected function buildMiterCut(array $triangleXZ, float $yStart, float $yEnd, string $territory): array
    {
        return [
            'type' => 'triangular_prism',
            'remove_from' => $territory,
            'vertices_xz' => $triangleXZ,
            'y_range' => [
                'start' => $yStart,
                'end' => $yEnd,
            ],
            'miter_angle' => 45,
        ];
    }

    /**
     * Legacy method - wraps universal miter for end panel + stile.
     * @deprecated Use calculateUniversalMiter() instead
     */
    protected function calculateEndPanelStileMiter(array $endPanel, array $stile, string $side): array
    {
        $result = $this->calculateUniversalMiter($endPanel, $stile, $side);
        return [
            'end_panel' => $result['part_a'],
            'stile' => $result['part_b'],
        ];
    }

    /**
     * Validate that all parts "touch and add up" correctly.
     *
     * @param array $positions Output from generate3dPositions()
     * @param array $specs Original input specs
     * @return array Validation results
     */
    public function validatePartPositions(array $positions, array $specs): array
    {
        $errors = [];
        $verifications = [];
        $tolerance = 0.001;

        $parts = $positions['parts'];
        $cabW = $specs['width'];
        $cabD = $specs['depth'];
        $boxH = $positions['cabinet_envelope']['box_height'];
        $toeKick = $specs['toe_kick_height'];

        // Get assembly rule and material thickness
        $sidesOnBottom = $specs['sides_on_bottom'] ?? true;
        $bottomThickness = $parts['bottom']['dimensions']['h'] ?? 0.75;

        // 1. Bottom panel at Y=0
        $bottomY = $parts['bottom']['position']['y'] ?? null;
        if ($bottomY !== null && abs($bottomY) > $tolerance) {
            $errors[] = "Bottom panel Y should be 0, got {$bottomY}";
        } else {
            $verifications[] = "✅ Bottom panel at Y=0";
        }

        // 2. Check side positions based on assembly rule
        $leftY = $parts['left_side']['position']['y'] ?? null;
        $rightY = $parts['right_side']['position']['y'] ?? null;

        if ($sidesOnBottom) {
            // TCS Standard: Sides sit ON TOP of bottom panel (Y = bottom thickness)
            if ($leftY !== null && abs($leftY - $bottomThickness) > $tolerance) {
                $errors[] = "Left side Y should be {$bottomThickness} (on top of bottom), got {$leftY}";
            } else {
                $verifications[] = "✅ Left side sits ON TOP of bottom (Y={$bottomThickness})";
            }
            if ($rightY !== null && abs($rightY - $bottomThickness) > $tolerance) {
                $errors[] = "Right side Y should be {$bottomThickness} (on top of bottom), got {$rightY}";
            } else {
                $verifications[] = "✅ Right side sits ON TOP of bottom (Y={$bottomThickness})";
            }
        } else {
            // Alternative: Sides at Y=0
            if ($leftY !== null && abs($leftY) > $tolerance) {
                $errors[] = "Left side Y should be 0, got {$leftY}";
            } else {
                $verifications[] = "✅ Left side at Y=0";
            }
            if ($rightY !== null && abs($rightY) > $tolerance) {
                $errors[] = "Right side Y should be 0, got {$rightY}";
            } else {
                $verifications[] = "✅ Right side at Y=0";
            }
        }

        // 3. Toe kick in negative Y
        $toeKickY = $parts['toe_kick']['position']['y'] ?? null;
        $expectedToeKickY = -$toeKick;
        if ($toeKickY !== null && abs($toeKickY - $expectedToeKickY) > $tolerance) {
            $errors[] = "Toe kick Y should be {$expectedToeKickY}, got {$toeKickY}";
        } else {
            $verifications[] = "✅ Toe kick at Y=-{$toeKick} (below box)";
        }

        // 4. Top of toe kick touches Y=0
        $toeKickTop = $toeKickY + ($parts['toe_kick']['dimensions']['h'] ?? 0);
        if (abs($toeKickTop) > $tolerance) {
            $errors[] = "Top of toe kick should touch Y=0, got {$toeKickTop}";
        } else {
            $verifications[] = "✅ Top of toe kick touches Y=0";
        }

        // 5. Left side at X=0
        $leftX = $parts['left_side']['position']['x'] ?? null;
        if ($leftX !== null && abs($leftX) > $tolerance) {
            $errors[] = "Left side X should be 0, got {$leftX}";
        } else {
            $verifications[] = "✅ Left side at X=0";
        }

        // 6. Right side ends at cabinet width
        $rightX = $parts['right_side']['position']['x'] ?? null;
        $rightW = $parts['right_side']['dimensions']['w'] ?? 0;
        $rightEnd = $rightX + $rightW;
        if (abs($rightEnd - $cabW) > $tolerance) {
            $errors[] = "Right side should end at X={$cabW}, ends at {$rightEnd}";
        } else {
            $verifications[] = "✅ Right side ends at X={$cabW}";
        }

        // 7. Back panel ends at cabinet depth
        $backZ = $parts['back']['position']['z'] ?? null;
        $backD = $parts['back']['dimensions']['d'] ?? 0;
        $backEnd = $backZ + $backD;
        if (abs($backEnd - $cabD) > $tolerance) {
            $errors[] = "Back panel should end at Z={$cabD}, ends at {$backEnd}";
        } else {
            $verifications[] = "✅ Back panel ends at Z={$cabD}";
        }

        // 7b. Back panel position/width based on assembly rule
        $backInsetFromSides = $specs['back_inset_from_sides'] ?? false;
        $backX = $parts['back']['position']['x'] ?? null;
        $backW = $parts['back']['dimensions']['w'] ?? 0;
        $backThickness = $parts['back']['dimensions']['d'] ?? 0.75;
        $sideThickness = $parts['left_side']['dimensions']['w'] ?? 0.75;

        if ($backInsetFromSides) {
            // Back fits between sides
            $expectedBackX = $sideThickness;
            $expectedBackW = $cabW - (2 * $sideThickness);
            if ($backX !== null && abs($backX - $expectedBackX) > $tolerance) {
                $errors[] = "Back panel X should be {$expectedBackX} (inset from sides), got {$backX}";
            } else {
                $verifications[] = "✅ Back panel inset from sides (X={$expectedBackX})";
            }
            if (abs($backW - $expectedBackW) > $tolerance) {
                $errors[] = "Back panel width should be {$expectedBackW}, got {$backW}";
            } else {
                $verifications[] = "✅ Back panel fits between sides (width={$backW}\")";
            }
        } else {
            // TCS Standard: Full width back
            if ($backX !== null && abs($backX) > $tolerance) {
                $errors[] = "Back panel X should be 0 (full width), got {$backX}";
            } else {
                $verifications[] = "✅ Back panel starts at X=0 (full width)";
            }
            if (abs($backW - $cabW) > $tolerance) {
                $errors[] = "Back panel width should be {$cabW}, got {$backW}";
            } else {
                $verifications[] = "✅ Back panel is full cabinet width ({$cabW}\")";
            }
        }

        // 7c. Sides and bottom depth based on assembly rule
        $sideDepth = $parts['left_side']['dimensions']['d'] ?? 0;
        $bottomDepth = $parts['bottom']['dimensions']['d'] ?? 0;

        if ($backInsetFromSides) {
            // Sides go full depth, bottom stops at back
            if (abs($sideDepth - $cabD) > $tolerance) {
                $errors[] = "Side depth should be {$cabD} (full depth), got {$sideDepth}";
            } else {
                $verifications[] = "✅ Sides are full depth (depth = {$sideDepth}\")";
            }
            $expectedBottomDepth = $cabD - $backThickness;
            if (abs($bottomDepth - $expectedBottomDepth) > $tolerance) {
                $errors[] = "Bottom depth should be {$expectedBottomDepth}, got {$bottomDepth}";
            } else {
                $verifications[] = "✅ Bottom stops at back (depth = {$bottomDepth}\")";
            }
        } else {
            // TCS Standard: Sides and bottom shortened for full-width back
            $expectedDepth = $cabD - $backThickness;
            if (abs($sideDepth - $expectedDepth) > $tolerance) {
                $errors[] = "Side depth should be {$expectedDepth} (cabD - back), got {$sideDepth}";
            } else {
                $verifications[] = "✅ Sides shortened for back (depth = {$sideDepth}\")";
            }
            if (abs($bottomDepth - $expectedDepth) > $tolerance) {
                $errors[] = "Bottom depth should be {$expectedDepth} (cabD - back), got {$bottomDepth}";
            } else {
                $verifications[] = "✅ Bottom shortened for back (depth = {$bottomDepth}\")";
            }
        }

        // 8. Sides height based on assembly rules
        // TCS Standard with stretchers_on_top: height = boxH - bottom - stretcher
        $stretchersOnTop = $specs['stretchers_on_top'] ?? true;
        $stretcherThickness = $specs['stretcher_thickness'] ?? 0.75;

        if ($stretchersOnTop && $sidesOnBottom) {
            $expectedSideHeight = $boxH - $bottomThickness - $stretcherThickness;
        } elseif ($sidesOnBottom) {
            $expectedSideHeight = $boxH - $bottomThickness;
        } elseif ($stretchersOnTop) {
            $expectedSideHeight = $boxH - $stretcherThickness;
        } else {
            $expectedSideHeight = $boxH;
        }

        $leftH = $parts['left_side']['dimensions']['h'] ?? 0;
        $rightH = $parts['right_side']['dimensions']['h'] ?? 0;
        if (abs($leftH - $expectedSideHeight) > $tolerance) {
            $errors[] = "Left side height should be {$expectedSideHeight}, got {$leftH}";
        } else {
            $verifications[] = "✅ Left side height = {$expectedSideHeight}\" (TCS: boxH - bottom - stretcher)";
        }
        if (abs($rightH - $expectedSideHeight) > $tolerance) {
            $errors[] = "Right side height should be {$expectedSideHeight}, got {$rightH}";
        } else {
            $verifications[] = "✅ Right side height = {$expectedSideHeight}\" (TCS: boxH - bottom - stretcher)";
        }

        // 9. Sides top based on assembly rules
        // TCS: Sides end below stretchers (top at boxH - stretcher_thickness)
        $expectedSideTop = $stretchersOnTop ? ($boxH - $stretcherThickness) : $boxH;
        $leftTop = $leftY + $leftH;
        $rightTop = $rightY + $rightH;
        if (abs($leftTop - $expectedSideTop) > $tolerance) {
            $errors[] = "Left side top should be at {$expectedSideTop}, got {$leftTop}";
        } else {
            $verifications[] = "✅ Left side ends below stretchers (Y={$leftTop})";
        }
        if (abs($rightTop - $expectedSideTop) > $tolerance) {
            $errors[] = "Right side top should be at {$expectedSideTop}, got {$rightTop}";
        } else {
            $verifications[] = "✅ Right side ends below stretchers (Y={$rightTop})";
        }

        // 10. DRAWER FIT VALIDATION - ensure drawers fit in openings
        // Check each drawer box against its opening and cabinet constraints
        $ffStile = $specs['face_frame_stile'] ?? 1.75;
        $ffRail = $specs['face_frame_rail'] ?? 1.5;
        $faceFrameOpeningW = $cabW - (2 * $ffStile);
        $insideDepth = $cabD - ($parts['back']['dimensions']['d'] ?? 0.75);

        $drawerNum = 1;
        while (isset($parts["drawer_{$drawerNum}_box"])) {
            $boxPart = $parts["drawer_{$drawerNum}_box"];
            $facePart = $parts["drawer_{$drawerNum}_face"] ?? null;

            $boxW = $boxPart['dimensions']['w'];
            $boxH = $boxPart['dimensions']['h'];
            $boxD = $boxPart['dimensions']['d'];
            $boxX = $boxPart['position']['x'];
            $boxY = $boxPart['position']['y'];
            $boxZ = $boxPart['position']['z'];

            // 10a. Drawer box width clearance - must be narrower than face frame opening
            $widthClearance = $faceFrameOpeningW - $boxW;
            $expectedWidthClearance = self::BLUM_SIDE_DEDUCTION; // 0.625"
            if ($widthClearance < $expectedWidthClearance - $tolerance) {
                $errors[] = "Drawer #{$drawerNum} width clearance ({$widthClearance}\") < Blum requirement ({$expectedWidthClearance}\")";
            } else {
                $verifications[] = "✅ Drawer #{$drawerNum} width fits opening ({$boxW}\" in {$faceFrameOpeningW}\" opening, clearance={$widthClearance}\")";
            }

            // 10b. Drawer box X position - must be centered on cabinet
            $expectedBoxX = ($cabW - $boxW) / 2;
            if (abs($boxX - $expectedBoxX) > $tolerance) {
                $errors[] = "Drawer #{$drawerNum} X should be {$expectedBoxX} (centered), got {$boxX}";
            } else {
                $verifications[] = "✅ Drawer #{$drawerNum} centered on cabinet (X={$boxX}\")";
            }

            // 10c. Drawer box must fit within entire opening (between cabinet sides)
            $sideThicknessCheck = $parts['left_side']['dimensions']['w'] ?? 0.75;
            $openingLeftX = $sideThicknessCheck;  // Inside of left side panel
            $openingRightX = $cabW - $sideThicknessCheck;  // Inside of right side panel
            $boxLeftX = $boxX;
            $boxRightX = $boxX + $boxW;

            $leftFits = $boxLeftX >= $openingLeftX - $tolerance;
            $rightFits = $boxRightX <= $openingRightX + $tolerance;

            if (!$leftFits) {
                $errors[] = "Drawer #{$drawerNum} left edge ({$boxLeftX}\") overlaps left side panel (opening starts at {$openingLeftX}\")";
            }
            if (!$rightFits) {
                $errors[] = "Drawer #{$drawerNum} right edge ({$boxRightX}\") overlaps right side panel (opening ends at {$openingRightX}\")";
            }
            if ($leftFits && $rightFits) {
                $leftClearance = $boxLeftX - $openingLeftX;
                $rightClearance = $openingRightX - $boxRightX;
                $verifications[] = "✅ Drawer #{$drawerNum} fits in opening (left clearance={$leftClearance}\", right clearance={$rightClearance}\")";
            }

            // 10d. Drawer box Z + depth must not exceed cabinet inside depth
            $boxZEnd = $boxZ + $boxD;
            if ($boxZEnd > $insideDepth + $tolerance) {
                $errors[] = "Drawer #{$drawerNum} Z end ({$boxZEnd}\") exceeds inside depth ({$insideDepth}\")";
            } else {
                $verifications[] = "✅ Drawer #{$drawerNum} depth fits cabinet (Z={$boxZ}\" to {$boxZEnd}\", inside depth={$insideDepth}\")";
            }

            // 10e. Drawer box height clearance within opening
            if ($facePart) {
                $faceH = $facePart['dimensions']['h'];
                $openingHeight = $faceH; // Face height defines opening height
                $heightClearance = $openingHeight - $boxH - self::BLUM_TOP_CLEARANCE - self::BLUM_BOTTOM_CLEARANCE;
                if ($heightClearance < -$tolerance) {
                    $errors[] = "Drawer #{$drawerNum} box too tall ({$boxH}\") for opening ({$openingHeight}\" with Blum clearances)";
                } else {
                    $verifications[] = "✅ Drawer #{$drawerNum} height fits opening ({$boxH}\" box in {$openingHeight}\" opening)";
                }
            }

            // 10f. Drawer box Z starts at face frame plane (Z=0)
            // Drawer face attaches to front of box and overlays face frame
            $expectedBoxZ = 0;
            if (abs($boxZ - $expectedBoxZ) > $tolerance) {
                $errors[] = "Drawer #{$drawerNum} Z should be {$expectedBoxZ}\" (at face frame plane), got {$boxZ}\"";
            } else {
                $verifications[] = "✅ Drawer #{$drawerNum} starts at face frame (Z={$boxZ}\")";
            }

            $drawerNum++;
        }

        return [
            'valid' => empty($errors),
            'status' => empty($errors) ? '✅ ALL POSITIONS VERIFIED' : '❌ POSITION ERRORS FOUND',
            'error_count' => count($errors),
            'verification_count' => count($verifications),
            'errors' => $errors,
            'verifications' => $verifications,
        ];
    }
}
