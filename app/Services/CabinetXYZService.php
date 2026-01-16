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

    // Blum TANDEM clearances
    public const BLUM_SIDE_DEDUCTION = 0.625;    // 5/8" total (drawer narrower than opening)
    public const BLUM_TOP_CLEARANCE = 0.25;       // 1/4" above drawer box
    public const BLUM_BOTTOM_CLEARANCE = 0.5625;  // 9/16" below drawer box

    // Standard gaps
    public const COMPONENT_GAP = 0.125;  // 1/8" between components

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
            $sideDepth = $cabD - $faceFrameThickness;  // Shortened for face frame only
            $bottomDepth = $cabD - $backThickness - $faceFrameThickness;  // Shortened for both
            $backWidth = $insideW;              // Back fits between sides
            $backXPosition = $sideThickness;    // Inset from sides
        } else {
            // TCS Standard: Back goes FULL WIDTH (sides/bottom shortened for BOTH front and back)
            $sideDepth = $cabD - $backThickness - $faceFrameThickness;    // Shortened for face frame AND back
            $bottomDepth = $cabD - $backThickness - $faceFrameThickness;  // Shortened for face frame AND back
            $backWidth = $cabW;                     // Back spans full width
            $backXPosition = 0;                     // Starts at X=0
        }

        // Z position for sides/bottom starts BEHIND face frame
        $boxZPosition = $faceFrameThickness;

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
        // TCS Standard (back_inset_from_sides=false): Full width back, sides/bottom shortened
        // Alternative (back_inset_from_sides=true): Back fits between sides
        $parts['back'] = [
            'part_name' => 'Back',
            'part_type' => 'cabinet_box',
            'position' => ['x' => $backXPosition, 'y' => 0, 'z' => $cabD - $backThickness],
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
        ];

        // TOP RAIL - fits between stiles (uses extended positions)
        // Rail length = distance between inner edges of stiles
        $openingW = $faceFrameWidth - (2 * $ffStile);
        // Rail X position starts at inner edge of left stile
        $railXPosition = $leftStileX + $ffStile;

        // TCS Rule: Top rail aligns with TOP of stretcher (top of box)
        // The top rail sits behind the stretcher, both tops at same Y level
        $topRailYTop = $boxH;  // Top of box = top of stretcher
        $topRailYBottom = $topRailYTop - $ffRail;

        $parts['top_rail'] = [
            'part_name' => 'Top Rail',
            'part_type' => 'face_frame',
            'position' => ['x' => $railXPosition, 'y' => $topRailYBottom, 'z' => 0],
            'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => self::PLYWOOD_3_4],
            'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => self::PLYWOOD_3_4],
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
        ];

        // BOTTOM RAIL - at bottom of box (Y = 0)
        $parts['bottom_rail'] = [
            'part_name' => 'Bottom Rail',
            'part_type' => 'face_frame',
            'position' => ['x' => $railXPosition, 'y' => 0, 'z' => 0],
            'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => self::PLYWOOD_3_4],
            'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => self::PLYWOOD_3_4],
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
        ];

        // ========================================
        // COMPONENT POSITIONS (False Fronts, Drawers)
        // FULL OVERLAY positioning - faces OVERLAP the rails
        // Stack from TOP going DOWN in Y-up system
        // Top of box = boxH, bottom = 0
        // ========================================
        $currentY = $boxH - $gap; // Start at top (just below top of box)
        $falseFronts = $specs['false_fronts'] ?? [];
        $drawerHeights = $specs['drawer_heights'] ?? [];

        // False fronts (at top)
        foreach ($falseFronts as $idx => $ff) {
            $ffNum = $idx + 1;
            $faceH = $ff['face_height'] ?? 7;

            // FALSE FRONT FACE - position is at bottom of face
            $faceY = $currentY - $faceH;
            $parts["false_front_{$ffNum}_face"] = [
                'part_name' => "False Front #{$ffNum} Face",
                'part_type' => 'false_front',
                'position' => ['x' => $railXPosition, 'y' => $faceY, 'z' => 0],
                'dimensions' => ['w' => $openingW, 'h' => $faceH, 'd' => self::PLYWOOD_3_4],
                'cut_dimensions' => ['width' => $faceH, 'length' => $faceFrameWidth, 'thickness' => self::PLYWOOD_3_4],
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
                'notes' => 'Full overlay - extends past stiles',
            ];

            // FALSE FRONT BACKING (horizontal stretcher)
            if ($ff['has_backing'] ?? true) {
                $backingH = $ff['backing_height'] ?? 3;
                $backingY = $faceY + ($faceH - $backingH) / 2;
                $parts["false_front_{$ffNum}_backing"] = [
                    'part_name' => "False Front #{$ffNum} Backing",
                    'part_type' => 'false_front_backing',
                    'position' => ['x' => $sideThickness, 'y' => $backingY, 'z' => self::PLYWOOD_3_4],
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
                ];
            }

            $currentY = $faceY - $gap; // Move down for next component

            // Mid rail after false front
            $midRailY = $faceY - $gap / 2 - $ffRail / 2;
            $parts["mid_rail_ff_{$ffNum}"] = [
                'part_name' => "Mid Rail (after FF#{$ffNum})",
                'part_type' => 'face_frame',
                'position' => ['x' => $railXPosition, 'y' => $midRailY, 'z' => 0],
                'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => self::PLYWOOD_3_4],
                'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => self::PLYWOOD_3_4],
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
            ];
        }

        // Drawers (from top to bottom in Y-up system)
        $drawerBoxes = $gate4['drawer_boxes'] ?? [];
        foreach ($drawerHeights as $idx => $drawerFaceH) {
            $drawerNum = $idx + 1;
            $drawerType = ($idx === 0 && !empty($falseFronts)) ? 'u_drawer' : 'drawer';
            $drawerLabel = $drawerType === 'u_drawer' ? 'U-Shaped Drawer' : 'Drawer';

            // DRAWER FACE - position is at bottom of face
            $faceY = $currentY - $drawerFaceH;
            $parts["drawer_{$drawerNum}_face"] = [
                'part_name' => "{$drawerLabel} #{$drawerNum} Face",
                'part_type' => 'drawer_face',
                'position' => ['x' => $railXPosition, 'y' => $faceY, 'z' => 0],
                'dimensions' => ['w' => $openingW, 'h' => $drawerFaceH, 'd' => self::PLYWOOD_3_4],
                'cut_dimensions' => ['width' => $drawerFaceH, 'length' => $faceFrameWidth, 'thickness' => self::PLYWOOD_3_4],
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
            ];

            // DRAWER BOX (from Gate 4 calculations)
            if (isset($drawerBoxes[$idx])) {
                $box = $drawerBoxes[$idx];
                $boxW = $box['outputs']['box_width'] ?? 0;
                $boxHt = $box['outputs']['box_height_shop'] ?? $box['outputs']['box_height_exact'] ?? 0;
                $boxD = $box['outputs']['box_depth_shop'] ?? $box['outputs']['box_depth'] ?? 18;

                // Drawer box sits at bottom of opening + bottom clearance
                $drawerBoxY = $faceY + self::BLUM_BOTTOM_CLEARANCE;

                $parts["drawer_{$drawerNum}_box"] = [
                    'part_name' => "{$drawerLabel} #{$drawerNum} Box",
                    'part_type' => 'drawer_box',
                    'position' => [
                        'x' => $sideThickness + (self::BLUM_SIDE_DEDUCTION / 2),
                        'y' => $drawerBoxY,
                        'z' => 0.125,
                    ],
                    'dimensions' => ['w' => $boxW, 'h' => $boxHt, 'd' => $boxD],
                    'box_parts' => [
                        'sides' => ['qty' => 2, 'width' => $boxHt, 'length' => $boxD, 'thickness' => self::PLYWOOD_1_2],
                        'front_back' => ['qty' => 2, 'width' => $boxHt, 'length' => $boxW - (2 * self::PLYWOOD_1_2), 'thickness' => self::PLYWOOD_1_2],
                        'bottom' => ['qty' => 1, 'width' => $boxW - 0.125, 'length' => $boxD - 0.5, 'thickness' => self::PLYWOOD_1_4],
                    ],
                    'orientation' => [
                        'rotation' => 0,
                        'grain_direction' => 'none',
                        'face_up' => 'n/a',
                    ],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => ['top'],
                        'machining' => ['dado_1_4', 'locking_device_holes', 'rear_hook_holes'],
                    ],
                    'material' => '1/2" Plywood (sides), 1/4" (bottom)',
                ];
            }

            $currentY = $faceY - $gap; // Move down for next component

            // Mid rail between drawers (not after last one)
            if ($idx < count($drawerHeights) - 1) {
                $midRailY = $faceY - $gap / 2 - $ffRail / 2;
                $parts["mid_rail_drawer_{$drawerNum}"] = [
                    'part_name' => "Mid Rail (after Drawer #{$drawerNum})",
                    'part_type' => 'face_frame',
                    'position' => ['x' => $railXPosition, 'y' => $midRailY, 'z' => 0],
                    'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => self::PLYWOOD_3_4],
                    'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => self::PLYWOOD_3_4],
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
        $parts['back_stretcher'] = [
            'part_name' => 'Back Stretcher',
            'part_type' => 'stretcher',
            'position' => ['x' => $stretcherXPosition, 'y' => $boxH - $stretcherThickness, 'z' => $bottomDepth - $stretcherDepth],
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
