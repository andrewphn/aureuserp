<?php

namespace App\Services;

use App\Services\SheetNestingService;
use App\Services\CabinetXYZService;
use App\Services\TcsMaterialService;
use Webkul\Project\Models\Cabinet;
use Illuminate\Support\Collection;

/**
 * Cabinet Math Audit Service
 *
 * "Gates of Construction" - A comprehensive math audit system for cabinet fabrication.
 * Given a cabinet's basic dimensions, produces a full calculation breakdown showing
 * every mathematical step a woodworker needs to verify, plus a cut list for all plywood pieces.
 *
 * TCS Standards (Bryan Patton, Jan 2025):
 * - 3" stretchers (front-to-back depth)
 * - 4-1/2" toe kick height
 * - 3/4" full backs (not 1/4")
 * - 1-1/2" face frame stiles and rails
 * - 1/8" reveals and component gaps
 *
 * Reference: Master Plan and 9 Austin Lane bathroom vanity specifications
 */
class CabinetMathAuditService
{
    /**
     * Standard clearance constants (in inches)
     */
    public const REVEAL_TOP = 0.125;          // 1/8"
    public const REVEAL_BOTTOM = 0.125;       // 1/8"
    public const COMPONENT_GAP = 0.125;       // 1/8" between drawers/doors
    public const DOOR_TO_FF_GAP = 0.125;      // 1/8" door to face frame gap

    /**
     * Blum TANDEM 563H drawer slide clearances (1/2" drawer sides)
     */
    public const BLUM_SIDE_DEDUCTION = 0.625;    // 5/8" total (width deduction)
    public const BLUM_TOP_CLEARANCE = 0.25;      // 1/4"
    public const BLUM_BOTTOM_CLEARANCE = 0.5625; // 9/16"
    public const BLUM_HEIGHT_DEDUCTION = 0.8125; // 13/16" total

    /**
     * Material thicknesses
     */
    public const PLYWOOD_3_4 = 0.75;    // 3/4" plywood
    public const PLYWOOD_1_2 = 0.5;     // 1/2" plywood (drawer sides)
    public const PLYWOOD_1_4 = 0.25;    // 1/4" plywood (drawer bottom)
    public const HARDWOOD_5_4 = 1.0;    // 5/4 hardwood (1" actual) - face frames

    /**
     * TCS Cabinet Assembly Standards (Jan 2025 - from Brian/Levi)
     */
    public const WALL_GAP = 0.5;              // 1/2" gap from wall for shimming
    public const END_PANEL_GAP = 0.25;        // 1/4" gap between cabinet side and end panel
    public const END_PANEL_INSTALL_OVERAGE = 0.5;  // +1/2" on end panels for install adjustment
    public const DRAWER_CAVITY_CLEARANCE = 0.25;   // 1/4" clearance beyond slide length

    /**
     * Construction standards service
     */
    protected ?ConstructionStandardsService $standards = null;

    /**
     * XYZ coordinate service for 3D positions
     */
    protected ?CabinetXYZService $xyzService = null;

    public function __construct(?ConstructionStandardsService $standards = null, ?CabinetXYZService $xyzService = null)
    {
        $this->standards = $standards ?? app(ConstructionStandardsService::class);
        $this->xyzService = $xyzService ?? app(CabinetXYZService::class);
    }

    /**
     * Generate full audit report for a cabinet
     *
     * @param Cabinet|array $input Cabinet model or array of dimensions
     * @return array Complete audit with all gates, gaps, and cut list
     */
    public function generateFullAudit(Cabinet|array $input): array
    {
        // Normalize input to array format
        $specs = $this->normalizeInput($input);

        // Run all gate audits
        $gate1 = $this->auditGate1_CabinetBox($specs);
        $gate2 = $this->auditGate2_FaceFrameOpening($specs, $gate1);
        $gate3 = $this->auditGate3_ComponentLayout($specs, $gate2);
        $gate4 = $this->auditGate4_DrawerClearances($specs, $gate2, $gate3);
        $gate5 = $this->auditGate5_Stretchers($specs, $gate1);
        $gate6 = $this->auditGate6_FaceFramePieces($specs, $gate1);

        // Generate assessments and cut list
        $gapAssessment = $this->generateGapAssessment($specs, $gate3, $gate4);
        $cutList = $this->generateCutList($specs, $gate1, $gate4, $gate5, $gate6);

        // Generate 3D positions for all parts (delegated to CabinetXYZService)
        $positions3d = $this->xyzService->generate3dPositions($specs, $gate1, $gate4, $gate5, $gate6);

        // Apply miter joints if joint_type is 'miter'
        $positions3d = $this->xyzService->calculateMiterJoints($positions3d, $specs);

        // Run position validation (including drawer fit validation)
        $positions3d['validation'] = $this->xyzService->validatePartPositions($positions3d, $specs);

        // Build cabinet identification for Rhino/V-Carve export
        $cabinetIdentification = $this->buildCabinetIdentification($input);

        return [
            'input_specs' => $specs,
            'cabinet_id' => $cabinetIdentification['cabinet_id'],
            'project_number' => $cabinetIdentification['project_number'],
            'cabinet_number' => $cabinetIdentification['cabinet_number'],
            'full_code' => $cabinetIdentification['full_code'],
            'gates' => [
                'gate_1_cabinet_box' => $gate1,
                'gate_2_face_frame_opening' => $gate2,
                'gate_3_component_layout' => $gate3,
                'gate_4_drawer_clearances' => $gate4,
                'gate_5_stretchers' => $gate5,
                'gate_6_face_frame_pieces' => $gate6,
            ],
            'gap_assessment' => $gapAssessment,
            'cut_list' => $cutList,
            'shop_parts_count' => $this->generateShopPartsCount($cutList),
            'positions_3d' => $positions3d,
            'summary' => $this->generateSummary($gate3, $gapAssessment),
        ];
    }

    /**
     * Normalize input to standard array format
     *
     * Includes face frame style values from Construction Template for use by CabinetXYZService.
     */
    protected function normalizeInput(Cabinet|array $input): array
    {
        if ($input instanceof Cabinet) {
            // Get assembly rules from ConstructionStandardsService for this cabinet
            $sidesOnBottom = $this->standards->sidesOnBottom($input);
            $backInsetFromSides = $this->standards->backInsetFromSides($input);
            $stretchersOnTop = $this->standards->stretchersOnTop($input);

            // Get template for face frame style values
            $template = $this->standards->resolveTemplate($input);

            // Use persisted calculated values if available, otherwise calculate
            $boxHeight = $input->box_height_inches
                ?? ($input->height_inches - ($input->toe_kick_height_inches ?? 4.5));
            $internalDepth = $input->internal_depth_inches
                ?? ($input->depth_inches - ($input->back_panel_thickness ?? self::PLYWOOD_3_4) - ($input->back_wall_gap_inches ?? self::WALL_GAP));
            $drawerSlideLength = $input->max_slide_length_inches
                ?? $input->drawer_slide_length
                ?? 18;

            return [
                'width' => $input->length_inches ?? $input->width_inches ?? 24,
                'height' => $input->height_inches ?? 30,
                'depth' => $input->depth_inches ?? 24,
                'toe_kick_height' => $input->toe_kick_height_inches ?? 4.5,
                'toe_kick_recess' => $this->standards->getToeKickRecess($input),
                'face_frame_stile' => $input->face_frame_stile_width ?? 1.5,
                'face_frame_rail' => $input->face_frame_rail_width ?? 1.5,
                'face_frame_thickness' => $this->standards->getFaceFrameThickness($input),
                // Persisted calculated values (centralized source of truth)
                'box_height' => $boxHeight,
                'internal_depth' => $internalDepth,
                'face_frame_depth' => $input->face_frame_depth_inches ?? ($input->face_frame_stile_width ?? 1.5),
                'drawer_depth' => $input->drawer_depth_inches ?? $drawerSlideLength,
                'drawer_clearance' => $input->drawer_clearance_inches ?? self::DRAWER_CAVITY_CLEARANCE,
                'back_wall_gap' => $input->back_wall_gap_inches ?? self::WALL_GAP,
                'depth_validated' => $input->depth_validated ?? false,
                'depth_validation_message' => $input->depth_validation_message,
                'max_slide_length' => $input->max_slide_length_inches,
                'calculated_at' => $input->calculated_at,
                'face_frame_style' => $input->face_frame_style ?? ($template->default_face_frame_style ?? CabinetXYZService::STYLE_FULL_OVERLAY),
                'side_panel_thickness' => $input->side_panel_thickness ?? self::PLYWOOD_3_4,
                'back_panel_thickness' => $input->back_panel_thickness ?? self::PLYWOOD_3_4,
                'drawer_heights' => $this->extractDrawerHeights($input),
                'drawer_slide_length' => $input->drawer_slide_length ?? 18,
                'false_fronts' => $this->extractFalseFronts($input),
                // Assembly Rules (from ConstructionStandardsService)
                'sides_on_bottom' => $sidesOnBottom,
                'back_inset_from_sides' => $backInsetFromSides,
                'stretchers_on_top' => $stretchersOnTop,
                // General Construction Values (from Template)
                'reveal_top' => $template->reveal_top ?? self::REVEAL_TOP,
                'reveal_bottom' => $template->reveal_bottom ?? self::REVEAL_BOTTOM,
                'component_gap' => $template->component_gap ?? self::COMPONENT_GAP,
                'door_to_ff_gap' => $template->door_side_reveal ?? self::DOOR_TO_FF_GAP,
                'wall_gap' => $template->back_wall_gap ?? self::WALL_GAP,
                'end_panel_gap' => $template->finished_end_gap ?? self::END_PANEL_GAP,
                'end_panel_install_overage' => $template->end_panel_install_overage ?? self::END_PANEL_INSTALL_OVERAGE,
                'drawer_cavity_clearance' => $template->drawer_cavity_clearance ?? self::DRAWER_CAVITY_CLEARANCE,
                // Face Frame Style Values (from Template - for CabinetXYZService)
                'frameless_reveal_gap' => $template->frameless_reveal_gap ?? 0.09375,
                'frameless_bottom_reveal' => $template->frameless_bottom_reveal ?? 0,
                'face_frame_reveal_gap' => $template->face_frame_reveal_gap ?? 0.125,
                'face_frame_bottom_reveal' => $template->face_frame_bottom_reveal ?? 0.125,
                'full_overlay_amount' => $template->full_overlay_amount ?? 1.25,
                'full_overlay_reveal_gap' => $template->full_overlay_reveal_gap ?? 0.125,
                'full_overlay_bottom_reveal' => $template->full_overlay_bottom_reveal ?? 0,
                'inset_reveal_gap' => $template->inset_reveal_gap ?? 0.0625,
                'inset_bottom_reveal' => $template->inset_bottom_reveal ?? 0.0625,
                'partial_overlay_amount' => $template->partial_overlay_amount ?? 0.375,
                'partial_overlay_reveal_gap' => $template->partial_overlay_reveal_gap ?? 0.125,
                'partial_overlay_bottom_reveal' => $template->partial_overlay_bottom_reveal ?? 0.125,
            ];
        }

        // Array input - fill in defaults using ConstructionStandardsService
        $defaults = [
            'width' => 24,
            'height' => 30,
            'depth' => 24,
            'toe_kick_height' => 4.5,
            'face_frame_stile' => 1.5,
            'face_frame_rail' => 1.5,
            'face_frame_thickness' => self::HARDWOOD_5_4,  // 5/4 hardwood = 1" actual (TCS standard)
            'face_frame_style' => CabinetXYZService::STYLE_FULL_OVERLAY,  // TCS default: full overlay
            'side_panel_thickness' => self::PLYWOOD_3_4,
            'back_panel_thickness' => self::PLYWOOD_3_4,
            'drawer_heights' => [],
            'drawer_slide_length' => 18,
            'false_fronts' => [],
            // TCS Assembly Standards
            'cabinet_type' => 'base',           // base, sink_base, wall, tall
            'has_end_panels' => false,          // Decorative end panels (mitered)
            // Calculated depth breakdown fields (TCS standard formula)
            'box_height' => null,  // Will be calculated below if not provided
            'internal_depth' => null,
            'face_frame_depth' => null,
            'drawer_depth' => null,
            'drawer_clearance' => self::DRAWER_CAVITY_CLEARANCE,
            'back_wall_gap' => self::WALL_GAP,
            'depth_validated' => false,
            'depth_validation_message' => null,
            'max_slide_length' => null,
            'calculated_at' => null,
            // General Construction Values (use ConstructionStandardsService fallbacks)
            'reveal_top' => ConstructionStandardsService::getFallbackDefault('reveal_top', self::REVEAL_TOP),
            'reveal_bottom' => ConstructionStandardsService::getFallbackDefault('reveal_bottom', self::REVEAL_BOTTOM),
            'component_gap' => ConstructionStandardsService::getFallbackDefault('component_gap', self::COMPONENT_GAP),
            'door_to_ff_gap' => ConstructionStandardsService::getFallbackDefault('door_side_reveal', self::DOOR_TO_FF_GAP),
            'wall_gap' => ConstructionStandardsService::getFallbackDefault('back_wall_gap', self::WALL_GAP),
            'end_panel_gap' => ConstructionStandardsService::getFallbackDefault('finished_end_gap', self::END_PANEL_GAP),
            'end_panel_install_overage' => ConstructionStandardsService::getFallbackDefault('end_panel_install_overage', self::END_PANEL_INSTALL_OVERAGE),
            'drawer_cavity_clearance' => ConstructionStandardsService::getFallbackDefault('drawer_cavity_clearance', self::DRAWER_CAVITY_CLEARANCE),
            // Face Frame Style Values (use ConstructionStandardsService fallbacks)
            'frameless_reveal_gap' => ConstructionStandardsService::getFallbackDefault('frameless_reveal_gap', 0.09375),
            'frameless_bottom_reveal' => ConstructionStandardsService::getFallbackDefault('frameless_bottom_reveal', 0),
            'face_frame_reveal_gap' => ConstructionStandardsService::getFallbackDefault('face_frame_reveal_gap', 0.125),
            'face_frame_bottom_reveal' => ConstructionStandardsService::getFallbackDefault('face_frame_bottom_reveal', 0.125),
            'full_overlay_amount' => ConstructionStandardsService::getFallbackDefault('full_overlay_amount', 1.25),
            'full_overlay_reveal_gap' => ConstructionStandardsService::getFallbackDefault('full_overlay_reveal_gap', 0.125),
            'full_overlay_bottom_reveal' => ConstructionStandardsService::getFallbackDefault('full_overlay_bottom_reveal', 0),
            'inset_reveal_gap' => ConstructionStandardsService::getFallbackDefault('inset_reveal_gap', 0.0625),
            'inset_bottom_reveal' => ConstructionStandardsService::getFallbackDefault('inset_bottom_reveal', 0.0625),
            'partial_overlay_amount' => ConstructionStandardsService::getFallbackDefault('partial_overlay_amount', 0.375),
            'partial_overlay_reveal_gap' => ConstructionStandardsService::getFallbackDefault('partial_overlay_reveal_gap', 0.125),
            'partial_overlay_bottom_reveal' => ConstructionStandardsService::getFallbackDefault('partial_overlay_bottom_reveal', 0.125),
            // Assembly Rules (from ConstructionStandardsService)
            'sides_on_bottom' => true,          // TCS: Sides sit ON TOP of bottom panel
            'back_inset_from_sides' => false,   // TCS: Back goes FULL WIDTH (sides/bottom shortened)
            'stretchers_on_top' => true,        // TCS: Stretchers sit ON TOP of sides = FULL WIDTH
        ];

        $merged = array_merge($defaults, $input);

        // Calculate derived values if not provided
        if ($merged['box_height'] === null) {
            $merged['box_height'] = $merged['height'] - $merged['toe_kick_height'];
        }
        if ($merged['internal_depth'] === null) {
            $merged['internal_depth'] = $merged['depth'] - $merged['back_panel_thickness'] - $merged['back_wall_gap'];
        }
        if ($merged['face_frame_depth'] === null) {
            $merged['face_frame_depth'] = $merged['face_frame_stile'];
        }
        if ($merged['drawer_depth'] === null) {
            $merged['drawer_depth'] = $merged['drawer_slide_length'];
        }
        if ($merged['max_slide_length'] === null) {
            // Calculate max slide that fits
            $available = $merged['internal_depth'] - $merged['drawer_clearance'];
            foreach ([21, 18, 15, 12, 9] as $len) {
                if ($available >= $len) {
                    $merged['max_slide_length'] = $len;
                    break;
                }
            }
        }

        return $merged;
    }

    /**
     * Extract drawer heights from cabinet model
     */
    protected function extractDrawerHeights(Cabinet $cabinet): array
    {
        $heights = [];

        // Try sections first
        if ($cabinet->sections) {
            foreach ($cabinet->sections as $section) {
                if ($section->drawers) {
                    foreach ($section->drawers as $drawer) {
                        $heights[] = $drawer->front_height_inches ?? $drawer->height_inches ?? 6;
                    }
                }
            }
        }

        // Fallback to direct drawers
        if (empty($heights) && method_exists($cabinet, 'drawers')) {
            try {
                foreach ($cabinet->drawers as $drawer) {
                    $heights[] = $drawer->front_height_inches ?? $drawer->height_inches ?? 6;
                }
            } catch (\Exception $e) {
                // No drawers
            }
        }

        return $heights;
    }

    /**
     * Extract false fronts from cabinet model
     *
     * TCS Rule (Bryan Patton, Jan 2025):
     * All false fronts have backing that doubles as stretcher.
     * Backing is 3/4" thick plywood, laid FLAT (horizontal), spanning cabinet width.
     */
    protected function extractFalseFronts(Cabinet $cabinet): array
    {
        $falseFronts = [];

        // Try sections first
        if ($cabinet->sections) {
            foreach ($cabinet->sections as $section) {
                if ($section->falseFronts) {
                    foreach ($section->falseFronts as $ff) {
                        $falseFronts[] = [
                            'face_height' => $ff->height_inches ?? $ff->face_height_inches ?? 7,
                            'has_backing' => $ff->has_backing ?? true,
                            'backing_height' => $ff->backing_height_inches ?? 3, // Standard 3" depth
                            'backing_thickness' => $ff->backing_thickness_inches ?? self::PLYWOOD_3_4,
                            'backing_material' => $ff->backing_material ?? '3/4" Plywood',
                            'backing_is_stretcher' => $ff->backing_is_stretcher ?? true,
                        ];
                    }
                }
            }
        }

        // Fallback to direct falseFronts
        if (empty($falseFronts) && method_exists($cabinet, 'falseFronts')) {
            try {
                foreach ($cabinet->falseFronts as $ff) {
                    $falseFronts[] = [
                        'face_height' => $ff->height_inches ?? $ff->face_height_inches ?? 7,
                        'has_backing' => $ff->has_backing ?? true,
                        'backing_height' => $ff->backing_height_inches ?? 3,
                        'backing_thickness' => $ff->backing_thickness_inches ?? self::PLYWOOD_3_4,
                        'backing_material' => $ff->backing_material ?? '3/4" Plywood',
                        'backing_is_stretcher' => $ff->backing_is_stretcher ?? true,
                    ];
                }
            } catch (\Exception $e) {
                // No false fronts
            }
        }

        return $falseFronts;
    }

    // =========================================================================
    // GATE 1: CABINET BOX DIMENSIONS
    // =========================================================================

    /**
     * Gate 1: Calculate cabinet box dimensions
     *
     * Input: Overall cabinet dimensions
     * Output: Box height, inside width, inside depth
     */
    public function auditGate1_CabinetBox(array $specs): array
    {
        $cabinetWidth = $specs['width'];
        $cabinetHeight = $specs['height'];
        $cabinetDepth = $specs['depth'];
        $toeKickHeight = $specs['toe_kick_height'];
        $sidePanelThickness = $specs['side_panel_thickness'];
        $backPanelThickness = $specs['back_panel_thickness'];
        $cabinetType = $specs['cabinet_type'] ?? 'base';

        // Box Height = Cabinet Height - Toe Kick
        $boxHeight = $cabinetHeight - $toeKickHeight;

        // TCS Rule for Side Panel Heights:
        // - NORMAL base: Sides are 3/4" SHORTER because stretchers sit ON TOP of sides
        // - SINK base: NO stretchers, so sides go FULL HEIGHT (compensates for missing stretchers)
        $isSinkBase = in_array($cabinetType, ['sink_base', 'kitchen_sink']);
        $stretcherThickness = self::PLYWOOD_3_4;
        $sidePanelHeight = $isSinkBase
            ? $boxHeight                         // Sink: Full height (no stretchers)
            : $boxHeight - $stretcherThickness;  // Normal: 3/4" shorter (stretchers on top)

        // Inside Width = Cabinet Width - (2 × Side Thickness)
        $insideWidth = $cabinetWidth - (2 * $sidePanelThickness);

        // Face Frame Thickness (front of cabinet)
        $faceFrameThickness = $specs['face_frame_thickness'] ?? self::PLYWOOD_3_4;

        // Inside Depth = Cabinet Depth - Back Thickness - Face Frame Thickness
        // TCS Rule: Sides/bottom fit BETWEEN face frame (front) and back panel (rear)
        $insideDepth = $cabinetDepth - $backPanelThickness - $faceFrameThickness;

        // TCS Rule: Wall gap calculation
        // Cabinet sits 1.25" from wall = 0.75" back + 0.5" shimming gap
        $wallGap = $specs['wall_gap'] ?? self::WALL_GAP;
        $distanceFromWall = $backPanelThickness + $wallGap;

        $calculations = [
            [
                'name' => 'Box Height',
                'formula' => 'Cabinet Height - Toe Kick',
                'values' => "{$cabinetHeight}\" - {$toeKickHeight}\"",
                'result' => $boxHeight,
                'result_formatted' => $this->formatInches($boxHeight),
            ],
            [
                'name' => 'Inside Width',
                'formula' => 'Cabinet Width - (2 × Side Thickness)',
                'values' => "{$cabinetWidth}\" - (2 × {$sidePanelThickness}\")",
                'result' => $insideWidth,
                'result_formatted' => $this->formatInches($insideWidth),
            ],
            [
                'name' => 'Inside Depth',
                'formula' => 'Cabinet Depth - Back Thickness - Face Frame Thickness',
                'values' => "{$cabinetDepth}\" - {$backPanelThickness}\" - {$faceFrameThickness}\"",
                'result' => $insideDepth,
                'result_formatted' => $this->formatInches($insideDepth),
                'note' => 'TCS Rule: Sides/bottom fit BETWEEN face frame and back panel',
            ],
            [
                'name' => 'Distance From Wall',
                'formula' => 'Back Thickness + Wall Gap',
                'values' => "{$backPanelThickness}\" + {$wallGap}\"",
                'result' => $distanceFromWall,
                'result_formatted' => $this->formatInches($distanceFromWall),
                'note' => 'Gap for shimming imperfect walls',
            ],
        ];

        // Add side panel height calculation (different for sink vs normal)
        if ($isSinkBase) {
            $calculations[] = [
                'name' => 'Side Panel Height (Sink Base)',
                'formula' => 'Box Height (full height - no stretchers)',
                'values' => "{$boxHeight}\"",
                'result' => $sidePanelHeight,
                'result_formatted' => $this->formatInches($sidePanelHeight),
                'note' => 'TCS Rule: Sink bases have NO stretchers - sides go full height to compensate',
            ];
        } else {
            $calculations[] = [
                'name' => 'Side Panel Height (Normal Base)',
                'formula' => 'Box Height - Stretcher Thickness',
                'values' => "{$boxHeight}\" - {$stretcherThickness}\"",
                'result' => $sidePanelHeight,
                'result_formatted' => $this->formatInches($sidePanelHeight),
                'note' => 'TCS Rule: Normal bases have stretchers ON TOP of sides',
            ];
        }

        return [
            'gate' => 1,
            'title' => 'Cabinet Box Dimensions',
            'calculations' => $calculations,
            'outputs' => [
                'box_height' => $boxHeight,
                'side_panel_height' => $sidePanelHeight,  // Actual cut height for sides
                'inside_width' => $insideWidth,
                'inside_depth' => $insideDepth,
                'is_sink_base' => $isSinkBase,
                'stretcher_on_top' => $isSinkBase,
                'distance_from_wall' => $distanceFromWall,
            ],
        ];
    }

    // =========================================================================
    // GATE 2: FACE FRAME OPENING
    // =========================================================================

    /**
     * Gate 2: Calculate face frame opening dimensions
     *
     * Input: Cabinet width, box height, stile/rail widths
     * Output: Face frame opening width & height
     */
    public function auditGate2_FaceFrameOpening(array $specs, array $gate1): array
    {
        $cabinetWidth = $specs['width'];
        $boxHeight = $gate1['outputs']['box_height'];
        $stileWidth = $specs['face_frame_stile'];
        $railWidth = $specs['face_frame_rail'];

        // FF Opening Width = Cabinet Width - (2 × Stile Width)
        $openingWidth = $cabinetWidth - (2 * $stileWidth);

        // FF Opening Height = Box Height - (2 × Rail Width)
        $openingHeight = $boxHeight - (2 * $railWidth);

        return [
            'gate' => 2,
            'title' => 'Face Frame Opening',
            'calculations' => [
                [
                    'name' => 'FF Opening Width',
                    'formula' => 'Cabinet Width - (2 × Stile Width)',
                    'values' => "{$cabinetWidth}\" - (2 × {$stileWidth}\")",
                    'result' => $openingWidth,
                    'result_formatted' => $this->formatInches($openingWidth),
                ],
                [
                    'name' => 'FF Opening Height',
                    'formula' => 'Box Height - (2 × Rail Width)',
                    'values' => "{$boxHeight}\" - (2 × {$railWidth}\")",
                    'result' => $openingHeight,
                    'result_formatted' => $this->formatInches($openingHeight),
                ],
            ],
            'outputs' => [
                'opening_width' => $openingWidth,
                'opening_height' => $openingHeight,
            ],
        ];
    }

    // =========================================================================
    // GATE 3: COMPONENT LAYOUT
    // =========================================================================

    /**
     * Gate 3: Calculate component layout in opening (vertical stack)
     *
     * Input: FF opening height, component heights
     * Output: Position of each component, remaining space
     */
    public function auditGate3_ComponentLayout(array $specs, array $gate2): array
    {
        $openingHeight = $gate2['outputs']['opening_height'];
        $drawerHeights = $specs['drawer_heights'] ?? [];

        $topReveal = self::REVEAL_TOP;
        $bottomReveal = self::REVEAL_BOTTOM;
        $componentGap = self::COMPONENT_GAP;

        // Calculate consumed height
        $consumedHeight = $bottomReveal + $topReveal;

        // Add drawer heights and gaps
        $componentCount = count($drawerHeights);
        foreach ($drawerHeights as $height) {
            $consumedHeight += $height;
        }

        // Add gaps between components (n-1 gaps for n components)
        if ($componentCount > 1) {
            $consumedHeight += ($componentCount - 1) * $componentGap;
        }

        $remainingSpace = $openingHeight - $consumedHeight;
        $fitStatus = $remainingSpace >= 0 ? 'FITS' : 'DOES NOT FIT';

        // Calculate component positions (from bottom)
        $positions = [];
        $currentPosition = $bottomReveal;

        // Reverse drawer heights to position from bottom up
        $reversedHeights = array_reverse($drawerHeights);
        foreach ($reversedHeights as $index => $height) {
            $positions[] = [
                'component' => 'Drawer ' . (count($drawerHeights) - $index),
                'height' => $height,
                'position_from_bottom' => $currentPosition,
            ];
            $currentPosition += $height + $componentGap;
        }

        return [
            'gate' => 3,
            'title' => 'Component Layout (Vertical Stack)',
            'constants' => [
                'top_reveal' => $topReveal,
                'bottom_reveal' => $bottomReveal,
                'component_gap' => $componentGap,
            ],
            'calculations' => [
                [
                    'name' => 'Opening Height Available',
                    'result' => $openingHeight,
                    'result_formatted' => $this->formatInches($openingHeight),
                ],
                [
                    'name' => 'Total Consumed Height',
                    'breakdown' => $this->buildConsumedHeightBreakdown($drawerHeights, $bottomReveal, $topReveal, $componentGap),
                    'result' => $consumedHeight,
                    'result_formatted' => $this->formatInches($consumedHeight),
                ],
                [
                    'name' => 'Remaining Space',
                    'formula' => 'Opening Height - Consumed Height',
                    'values' => "{$openingHeight}\" - {$consumedHeight}\"",
                    'result' => $remainingSpace,
                    'result_formatted' => $this->formatInches($remainingSpace),
                ],
            ],
            'component_positions' => array_reverse($positions), // Back to top-down order
            'outputs' => [
                'consumed_height' => $consumedHeight,
                'remaining_space' => $remainingSpace,
                'fit_status' => $fitStatus,
                'status_ok' => $remainingSpace >= 0,
            ],
        ];
    }

    /**
     * Build breakdown of consumed height calculation
     */
    protected function buildConsumedHeightBreakdown(array $drawerHeights, float $bottomReveal, float $topReveal, float $componentGap): array
    {
        $breakdown = [];
        $breakdown[] = ['item' => 'Bottom Reveal', 'value' => $bottomReveal];

        $reversedHeights = array_reverse($drawerHeights);
        foreach ($reversedHeights as $index => $height) {
            $breakdown[] = ['item' => 'Drawer ' . (count($drawerHeights) - $index), 'value' => $height];
            if ($index < count($drawerHeights) - 1) {
                $breakdown[] = ['item' => 'Gap', 'value' => $componentGap];
            }
        }

        $breakdown[] = ['item' => 'Top Reveal', 'value' => $topReveal];

        return $breakdown;
    }

    // =========================================================================
    // GATE 4: DRAWER BOX CLEARANCES
    // =========================================================================

    /**
     * Gate 4: Calculate drawer box dimensions (Blum TANDEM)
     *
     * Input: FF opening width, drawer opening heights
     * Output: Drawer box width, height, depth
     */
    public function auditGate4_DrawerClearances(array $specs, array $gate2, array $gate3): array
    {
        $openingWidth = $gate2['outputs']['opening_width'];
        $drawerHeights = $specs['drawer_heights'] ?? [];
        $slideLength = $specs['drawer_slide_length'] ?? 18;
        $cabinetDepth = $specs['depth'];

        $drawerBoxes = [];

        foreach ($drawerHeights as $index => $openingHeight) {
            $drawerNumber = $index + 1;

            // Box Width = Opening Width - Side Deduction (5/8")
            $boxWidth = $openingWidth - self::BLUM_SIDE_DEDUCTION;

            // Box Height = Opening Height - Height Deduction (13/16")
            $exactBoxHeight = $openingHeight - self::BLUM_HEIGHT_DEDUCTION;
            // Round down to nearest 1/2" for shop
            $shopBoxHeight = floor($exactBoxHeight * 2) / 2;

            // Box Depth = Slide Length (typically matches slide)
            $boxDepth = $slideLength;
            // Shop depth adds 1/4" for bottom dado
            $shopBoxDepth = $boxDepth + 0.25;

            $drawerBoxes[] = [
                'drawer_number' => $drawerNumber,
                'opening_height' => $openingHeight,
                'calculations' => [
                    [
                        'name' => 'Box Width',
                        'formula' => 'Opening Width - Side Deduction',
                        'values' => "{$openingWidth}\" - " . self::BLUM_SIDE_DEDUCTION . "\"",
                        'result' => $boxWidth,
                        'result_formatted' => $this->formatInches($boxWidth),
                    ],
                    [
                        'name' => 'Box Height (exact)',
                        'formula' => 'Opening Height - Height Deduction',
                        'values' => "{$openingHeight}\" - " . self::BLUM_HEIGHT_DEDUCTION . "\"",
                        'result' => $exactBoxHeight,
                        'result_formatted' => $this->formatInches($exactBoxHeight),
                    ],
                    [
                        'name' => 'Box Height (shop)',
                        'formula' => 'Round down to nearest 1/2"',
                        'result' => $shopBoxHeight,
                        'result_formatted' => $this->formatInches($shopBoxHeight),
                    ],
                    [
                        'name' => 'Box Depth',
                        'formula' => 'Slide Length',
                        'result' => $boxDepth,
                        'result_formatted' => $this->formatInches($boxDepth),
                    ],
                    [
                        'name' => 'Box Depth (shop)',
                        'formula' => 'Slide Length + 1/4" (dado)',
                        'result' => $shopBoxDepth,
                        'result_formatted' => $this->formatInches($shopBoxDepth),
                    ],
                ],
                'outputs' => [
                    'box_width' => $boxWidth,
                    'box_height_theoretical' => $exactBoxHeight,
                    'box_height_exact' => $exactBoxHeight,
                    'box_height_shop' => $shopBoxHeight,
                    'box_depth' => $boxDepth,
                    'box_depth_shop' => $shopBoxDepth,
                ],
            ];
        }

        // Validate cabinet depth for slide
        // TCS Rule (Brian Jan 2025): Drawer box is 18", cavity needs 18.25" minimum
        $minCavityDepth = $slideLength + self::DRAWER_CAVITY_CLEARANCE;  // 18" + 0.25" = 18.25"
        $cavityDepth = $specs['depth'] - ($specs['back_panel_thickness'] ?? self::PLYWOOD_3_4);  // Inside depth
        $depthValidation = [
            'drawer_box_depth' => $slideLength,
            'min_cavity_required' => $minCavityDepth,
            'actual_cavity' => $cavityDepth,
            'cabinet_depth' => $cabinetDepth,
            'status' => $cavityDepth >= $minCavityDepth ? 'OK' : 'INSUFFICIENT',
            'is_at_minimum' => abs($cavityDepth - $minCavityDepth) < 0.125,
            'tcs_note' => "Drawer box: {$slideLength}\", Cavity minimum: {$minCavityDepth}\"",
        ];

        return [
            'gate' => 4,
            'title' => 'Drawer Box Dimensions (Blum TANDEM 563H)',
            'clearance_constants' => [
                'side_deduction_total' => self::BLUM_SIDE_DEDUCTION,
                'top_clearance' => self::BLUM_TOP_CLEARANCE,
                'bottom_clearance' => self::BLUM_BOTTOM_CLEARANCE,
                'height_deduction_total' => self::BLUM_HEIGHT_DEDUCTION,
            ],
            'drawer_boxes' => $drawerBoxes,
            'depth_validation' => $depthValidation,
            'outputs' => [
                'drawer_count' => count($drawerBoxes),
                'depth_ok' => $depthValidation['status'] === 'OK',
            ],
        ];
    }

    // =========================================================================
    // GATE 5: STRETCHER DIMENSIONS
    // =========================================================================

    /**
     * Gate 5: Calculate stretcher dimensions and positions
     *
     * TCS Rule (Bryan Patton, Jan 2025):
     * - "The stretcher is centered on the drawer faces"
     * - "The stretcher splits the gap between drawer faces"
     * - Bottom drawer face lines up with bottom of cabinet (no bottom overlap)
     *
     * Input: Inside width, drawer count, drawer face heights
     * Output: Stretcher width, depth, count, vertical positions
     */
    public function auditGate5_Stretchers(array $specs, array $gate1): array
    {
        $insideWidth = $gate1['outputs']['inside_width'];
        $boxHeight = $gate1['outputs']['box_height'];
        $drawerHeights = $specs['drawer_heights'] ?? [];
        $drawerCount = count($drawerHeights);
        $cabinetType = $specs['cabinet_type'] ?? 'base';

        // TCS Standard: 3" stretcher depth, 3/4" thickness
        $stretcherDepth = 3.0;
        $stretcherThickness = self::PLYWOOD_3_4;
        $stretcherGap = self::COMPONENT_GAP; // 1/8" gap between drawer faces

        // TCS Rule: Sink bases have NO stretchers at all
        // The side panels extend up +3/4" to compensate for missing stretchers
        // This provides countertop support without internal stretchers
        $isSinkBase = in_array($cabinetType, ['sink_base', 'kitchen_sink']);

        // Sink bases: NO stretchers
        // Normal bases: 2 (front + back) + (drawer_count - 1) for drawer supports
        if ($isSinkBase) {
            return [
                'gate' => 5,
                'title' => 'Stretcher Dimensions & Positions',
                'tcs_standard' => [
                    'stretcher_depth' => '3"',
                    'stretcher_thickness' => '3/4"',
                    'gap_between_faces' => '1/8"',
                    'rule' => 'Stretcher splits the gap between drawer faces',
                ],
                'sink_base_note' => 'TCS Rule: Sink bases have NO stretchers - sides extend +3/4" to compensate',
                'calculations' => [
                    [
                        'name' => 'Stretcher Count',
                        'formula' => 'Sink Base = No stretchers',
                        'result' => 0,
                        'note' => 'Side panels compensate by extending +3/4" for countertop support',
                    ],
                ],
                'position_calculations' => [],
                'stretchers' => [],
                'outputs' => [
                    'stretcher_width' => $insideWidth,
                    'stretcher_depth' => $stretcherDepth,
                    'stretcher_thickness' => $stretcherThickness,
                    'stretcher_count' => 0,
                    'box_height' => $boxHeight,
                    'is_sink_base' => true,
                ],
            ];
        }

        // Normal base cabinet - has front/back + drawer support stretchers
        $drawerSupportCount = max(0, $drawerCount - 1);
        $stretcherCount = 2 + $drawerSupportCount;

        $stretchers = [];

        // Front stretcher (sandwiched at top of box)
        $stretchers[] = [
            'number' => 1,
            'position' => 'Front',
            'width' => $insideWidth,
            'depth' => $stretcherDepth,
            'thickness' => $stretcherThickness,
            'position_from_top' => 0,
            'position_from_bottom' => $boxHeight,
            'mounting' => 'sandwiched',
            'note' => 'Sandwiched at top of box',
        ];

        // Back stretcher (sandwiched at top of box)
        $stretchers[] = [
            'number' => 2,
            'position' => 'Back',
            'width' => $insideWidth,
            'depth' => $stretcherDepth,
            'thickness' => $stretcherThickness,
            'position_from_top' => 0,
            'position_from_bottom' => $boxHeight,
            'mounting' => 'sandwiched',
            'note' => 'Sandwiched at top of box',
        ];

        // Drawer support stretchers - calculate vertical positions
        // TCS Rule: Stretcher splits the gap between drawer faces
        // Bottom drawer starts at bottom of box (no reveal at bottom)
        $positionCalculations = [];

        if ($drawerCount > 1) {
            // Calculate positions from BOTTOM up
            // Each stretcher goes above the drawer below it
            $currentPositionFromBottom = 0;

            // Sort drawer heights from bottom to top
            // (assuming array is ordered top-to-bottom, reverse it)
            $drawersBottomUp = array_reverse($drawerHeights);

            // Stretcher mounting offset derived from Blum TANDEM 563H specs:
            // Offset = Bottom clearance (9/16") - Reveal gap (1/8") = 7/16"
            // This positions the stretcher TOP at the bottom of the upper drawer's opening
            $blumBottomClearance = 0.5625;  // 9/16" (14mm) - Blum spec
            $stretcherMountingOffset = $blumBottomClearance - $stretcherGap;  // 0.5625 - 0.125 = 0.4375

            for ($i = 0; $i < count($drawersBottomUp) - 1; $i++) {
                $drawerFaceHeight = $drawersBottomUp[$i];
                $currentPositionFromBottom += $drawerFaceHeight;

                // Stretcher TOP position from bottom = sum of faces below + mounting offset
                // This positions the stretcher TOP at the correct height for slide mounting
                $stretcherTopFromBottom = $currentPositionFromBottom + $stretcherMountingOffset;
                $stretcherPositionFromBottom = $stretcherTopFromBottom - $stretcherThickness;
                $stretcherPositionFromTop = $boxHeight - $stretcherTopFromBottom;

                $stretchers[] = [
                    'number' => 3 + $i,
                    'position' => 'Drawer Support',
                    'width' => $insideWidth,
                    'depth' => $stretcherDepth,
                    'thickness' => $stretcherThickness,
                    'position_from_top' => $stretcherPositionFromTop,
                    'position_from_bottom' => $stretcherPositionFromBottom,
                    'stretcher_top_from_bottom' => $stretcherTopFromBottom,
                    'supports_drawer_above' => $i + 1, // Index of drawer above this stretcher
                ];

                $positionCalculations[] = [
                    'stretcher_number' => 3 + $i,
                    'drawer_below_height' => $drawerFaceHeight,
                    'cumulative_height' => $currentPositionFromBottom,
                    'mounting_offset' => $stretcherMountingOffset,
                    'stretcher_top_from_bottom' => $stretcherTopFromBottom,
                    'stretcher_bottom_from_bottom' => $stretcherPositionFromBottom,
                    'position_from_top' => $stretcherPositionFromTop,
                    'note' => 'Stretcher TOP at ' . $this->formatInches($stretcherTopFromBottom) . ' from bottom (CAD: 17-1/8" from top)',
                ];

                // Add gap for next iteration
                $currentPositionFromBottom += $stretcherGap;
            }
        }

        return [
            'gate' => 5,
            'title' => 'Stretcher Dimensions & Positions',
            'tcs_standard' => [
                'stretcher_depth' => '3"',
                'stretcher_thickness' => '3/4"',
                'gap_between_faces' => '1/8" (reveal gap)',
                'blum_bottom_clearance' => '9/16" (14mm) - Blum TANDEM 563H spec',
                'mounting_offset' => '7/16" (0.4375") = Bottom clearance - Reveal gap',
                'rule' => 'Stretcher TOP = Drawer face TOP + (Blum bottom clearance - Reveal gap)',
                'formula' => 'Position = Face height + 9/16" - 1/8" = Face height + 7/16"',
            ],
            'calculations' => [
                [
                    'name' => 'Stretcher Width',
                    'formula' => 'Inside Width (from Gate 1)',
                    'result' => $insideWidth,
                    'result_formatted' => $this->formatInches($insideWidth),
                ],
                [
                    'name' => 'Stretcher Count',
                    'formula' => '2 (front + back) + (drawer_count - 1)',
                    'values' => "2 + ({$drawerCount} - 1) = 2 + {$drawerSupportCount}",
                    'result' => $stretcherCount,
                    'note' => 'Bottom drawer mounts on cabinet bottom, not a stretcher',
                ],
            ],
            'position_calculations' => $positionCalculations,
            'stretchers' => $stretchers,
            'outputs' => [
                'stretcher_width' => $insideWidth,
                'stretcher_depth' => $stretcherDepth,
                'stretcher_thickness' => $stretcherThickness,
                'stretcher_count' => $stretcherCount,
                'box_height' => $boxHeight,
            ],
        ];
    }

    // =========================================================================
    // GATE 6: FACE FRAME PIECES
    // =========================================================================

    /**
     * Gate 6: Calculate face frame piece dimensions
     *
     * Input: Cabinet width, box height, stile/rail widths
     * Output: Individual stile and rail dimensions
     */
    public function auditGate6_FaceFramePieces(array $specs, array $gate1): array
    {
        $cabinetWidth = $specs['width'];
        $boxHeight = $gate1['outputs']['box_height'];
        $stileWidth = $specs['face_frame_stile'];
        $railWidth = $specs['face_frame_rail'];
        $drawerCount = count($specs['drawer_heights'] ?? []);

        // Stiles run full height of box
        $stileLength = $boxHeight;

        // Rails span between stiles
        $railLength = $cabinetWidth - (2 * $stileWidth);

        // Mid rails = drawer_count - 1 (dividers between drawers)
        $midRailCount = max(0, $drawerCount - 1);

        // TCS Standard: Face frame is 5/4 hardwood (1" actual), NOT 3/4" sheet goods
        $ffThickness = $specs['face_frame_thickness'] ?? self::HARDWOOD_5_4;

        $pieces = [
            'stiles' => [
                'qty' => 2,
                'width' => $stileWidth,
                'length' => $stileLength,
                'thickness' => $ffThickness,
                'notes' => 'Left and Right stiles (5/4 hardwood)',
            ],
            'top_rail' => [
                'qty' => 1,
                'width' => $railWidth,
                'length' => $railLength,
                'thickness' => $ffThickness,
                'notes' => 'Top rail (5/4 hardwood)',
            ],
            'bottom_rail' => [
                'qty' => 1,
                'width' => $railWidth,
                'length' => $railLength,
                'thickness' => $ffThickness,
                'notes' => 'Bottom rail (5/4 hardwood)',
            ],
        ];

        if ($midRailCount > 0) {
            $pieces['mid_rails'] = [
                'qty' => $midRailCount,
                'width' => $railWidth,
                'length' => $railLength,
                'thickness' => $ffThickness,
                'notes' => 'Mid rails between drawers (5/4 hardwood)',
            ];
        }

        return [
            'gate' => 6,
            'title' => 'Face Frame Cut List',
            'calculations' => [
                [
                    'name' => 'Stile Length',
                    'formula' => 'Box Height',
                    'result' => $stileLength,
                    'result_formatted' => $this->formatInches($stileLength),
                ],
                [
                    'name' => 'Rail Length',
                    'formula' => 'Cabinet Width - (2 × Stile Width)',
                    'values' => "{$cabinetWidth}\" - (2 × {$stileWidth}\")",
                    'result' => $railLength,
                    'result_formatted' => $this->formatInches($railLength),
                ],
                [
                    'name' => 'Mid Rail Count',
                    'formula' => 'Drawer Count - 1',
                    'values' => "{$drawerCount} - 1",
                    'result' => $midRailCount,
                ],
            ],
            'pieces' => $pieces,
            'outputs' => [
                'stile_length' => $stileLength,
                'rail_length' => $railLength,
                'mid_rail_count' => $midRailCount,
            ],
        ];
    }

    // =========================================================================
    // GAP ASSESSMENT
    // =========================================================================

    /**
     * Generate comprehensive gap assessment
     */
    public function generateGapAssessment(array $specs, array $gate3, array $gate4): array
    {
        $gaps = [];

        // Opening Reveals
        $gaps['opening_reveals'] = [
            [
                'location' => 'Top Reveal',
                'standard' => '1/8"',
                'standard_value' => self::REVEAL_TOP,
                'actual' => self::REVEAL_TOP,
                'status' => 'OK',
            ],
            [
                'location' => 'Bottom Reveal',
                'standard' => '1/8"',
                'standard_value' => self::REVEAL_BOTTOM,
                'actual' => self::REVEAL_BOTTOM,
                'status' => 'OK',
            ],
            [
                'location' => 'Between Drawers',
                'standard' => '1/8"',
                'standard_value' => self::COMPONENT_GAP,
                'actual' => self::COMPONENT_GAP,
                'status' => 'OK',
            ],
        ];

        // Drawer Clearances (Blum)
        $gaps['drawer_clearances'] = [
            [
                'location' => 'Side Clearance (total)',
                'standard' => '5/8"',
                'standard_value' => self::BLUM_SIDE_DEDUCTION,
                'actual' => self::BLUM_SIDE_DEDUCTION,
                'status' => 'OK',
            ],
            [
                'location' => 'Top Clearance',
                'standard' => '1/4"',
                'standard_value' => self::BLUM_TOP_CLEARANCE,
                'actual' => self::BLUM_TOP_CLEARANCE,
                'status' => 'OK',
            ],
            [
                'location' => 'Bottom Clearance',
                'standard' => '9/16"',
                'standard_value' => self::BLUM_BOTTOM_CLEARANCE,
                'actual' => self::BLUM_BOTTOM_CLEARANCE,
                'status' => 'OK',
            ],
        ];

        // Face Frame Gaps
        $gaps['face_frame_gaps'] = [
            [
                'location' => 'Door/Drawer to FF',
                'standard' => '1/8"',
                'standard_value' => self::DOOR_TO_FF_GAP,
                'actual' => self::DOOR_TO_FF_GAP,
                'status' => 'OK',
            ],
        ];

        // Cabinet Depth Check
        $depthValidation = $gate4['depth_validation'] ?? [];
        $gaps['cabinet_depth'] = [
            [
                'location' => 'Min for Slide',
                'standard' => $this->formatInches($depthValidation['min_required'] ?? 18.75),
                'standard_value' => $depthValidation['min_required'] ?? 18.75,
                'actual' => $depthValidation['actual'] ?? $specs['depth'],
                'status' => $depthValidation['is_at_minimum'] ?? false ? 'AT MIN' : ($depthValidation['status'] ?? 'OK'),
            ],
        ];

        // Layout Status
        $gaps['layout_status'] = [
            [
                'location' => 'Component Fit',
                'standard' => 'Components must fit in opening',
                'actual' => $gate3['outputs']['remaining_space'] ?? 0,
                'status' => ($gate3['outputs']['status_ok'] ?? true) ? 'OK' : 'OVERFLOW',
            ],
        ];

        return $gaps;
    }

    // =========================================================================
    // CUT LIST GENERATION
    // =========================================================================

    /**
     * Generate simplified shop parts count for verification
     *
     * TCS SHOP USE (Brian Jan 2025):
     * "What the guys need is a list of plywood parts - if they have 5 pieces
     * but the list has 6, they can figure out what they're missing by sizes"
     */
    protected function generateShopPartsCount(array $cutList): array
    {
        $counts = [
            'plywood_3_4' => ['count' => 0, 'pieces' => []],
            'plywood_1_2' => ['count' => 0, 'pieces' => []],
            'plywood_1_4' => ['count' => 0, 'pieces' => []],
            'hardwood_5_4' => ['count' => 0, 'pieces' => []],
        ];

        // Sections to count (skip machining_operations)
        $sectionsToCount = ['cabinet_box', 'stretchers', 'false_fronts', 'toe_kick', 'end_panels'];

        foreach ($sectionsToCount as $section) {
            if (!isset($cutList[$section]['pieces'])) continue;

            foreach ($cutList[$section]['pieces'] as $piece) {
                $thickness = $piece['thickness'] ?? 0.75;
                $qty = $piece['qty'] ?? 1;

                // Determine material category
                $category = match (true) {
                    $thickness >= 0.75 => 'plywood_3_4',
                    $thickness >= 0.5 => 'plywood_1_2',
                    default => 'plywood_1_4',
                };

                $counts[$category]['count'] += $qty;
                $counts[$category]['pieces'][] = [
                    'part' => $piece['part'],
                    'qty' => $qty,
                    'size' => ($piece['length_formatted'] ?? '') . ' x ' . ($piece['width_formatted'] ?? ''),
                ];
            }
        }

        // Face frame (5/4 hardwood)
        if (isset($cutList['face_frame']['pieces'])) {
            foreach ($cutList['face_frame']['pieces'] as $piece) {
                $qty = $piece['qty'] ?? 1;
                $counts['hardwood_5_4']['count'] += $qty;
                $counts['hardwood_5_4']['pieces'][] = [
                    'part' => $piece['part'],
                    'qty' => $qty,
                    'size' => ($piece['length_formatted'] ?? '') . ' x ' . ($piece['width_formatted'] ?? ''),
                ];
            }
        }

        // Drawer boxes (1/2" and 1/4")
        if (isset($cutList['drawer_boxes']['pieces'])) {
            foreach ($cutList['drawer_boxes']['pieces'] as $piece) {
                $thickness = $piece['thickness'] ?? 0.5;
                $qty = $piece['qty'] ?? 1;

                $category = $thickness <= 0.25 ? 'plywood_1_4' : 'plywood_1_2';
                $counts[$category]['count'] += $qty;
                $counts[$category]['pieces'][] = [
                    'part' => $piece['part'],
                    'qty' => $qty,
                    'size' => ($piece['length_formatted'] ?? '') . ' x ' . ($piece['width_formatted'] ?? ''),
                ];
            }
        }

        // Summary totals
        $totalPieces = $counts['plywood_3_4']['count']
            + $counts['plywood_1_2']['count']
            + $counts['plywood_1_4']['count']
            + $counts['hardwood_5_4']['count'];

        return [
            'total_pieces' => $totalPieces,
            'by_material' => [
                '3/4" Plywood' => $counts['plywood_3_4']['count'],
                '1/2" Plywood' => $counts['plywood_1_2']['count'],
                '1/4" Plywood' => $counts['plywood_1_4']['count'],
                '5/4 Hardwood' => $counts['hardwood_5_4']['count'],
            ],
            'details' => $counts,
            'shop_checklist' => $this->generateShopChecklist($counts),
        ];
    }

    /**
     * Generate simple shop checklist format
     */
    protected function generateShopChecklist(array $counts): array
    {
        $checklist = [];

        foreach ($counts as $material => $data) {
            if ($data['count'] === 0) continue;

            $materialName = match ($material) {
                'plywood_3_4' => '3/4" Plywood',
                'plywood_1_2' => '1/2" Plywood',
                'plywood_1_4' => '1/4" Plywood',
                'hardwood_5_4' => '5/4 Hardwood',
                default => $material,
            };

            $checklist[] = [
                'material' => $materialName,
                'total_count' => $data['count'],
                'parts' => array_map(fn($p) => "{$p['qty']}x {$p['part']} ({$p['size']})", $data['pieces']),
            ];
        }

        return $checklist;
    }

    /**
     * Generate complete cut list
     */
    public function generateCutList(array $specs, array $gate1, array $gate4, array $gate5, array $gate6): array
    {
        $cutList = [
            'cabinet_box' => $this->generateBoxCutList($specs, $gate1),
            'stretchers' => $this->generateStretcherCutList($gate5),
            'false_fronts' => $this->generateFalseFrontCutList($specs, $gate1),
            'face_frame' => $this->generateFaceFrameCutList($gate6),
            'toe_kick' => $this->generateToeKickCutList($specs, $gate1),
            'end_panels' => $this->generateEndPanelCutList($specs, $gate1),
            'drawer_boxes' => $this->generateDrawerBoxCutList($gate4),
            'machining_operations' => $this->generateMachiningOperations($specs, $gate1, $gate4),
        ];

        return $cutList;
    }

    /**
     * Generate machining operations (dados, drill holes, edge banding)
     *
     * TCS SHOP PRACTICE:
     * - Drawer boxes: 1/4" x 1/4" dado, 1/2" from bottom edge
     * - Shelf pins: 5mm holes, 2" from front/back edges, 2" vertical spacing
     * - Edge banding: Front edges of shelves only
     * - Blum TANDEM 563H mounting: Runner at 37mm, locking device holes on drawer
     */
    protected function generateMachiningOperations(array $specs, array $gate1, array $gate4): array
    {
        $operations = [];

        // ========================================
        // DRAWER BOX DADOS
        // ========================================
        $drawerCount = count($gate4['drawer_boxes'] ?? []);
        if ($drawerCount > 0) {
            $operations['drawer_dados'] = [
                'operation' => 'Dado Cut',
                'tool' => '1/4" dado blade or straight bit',
                'applies_to' => "All drawer box pieces ({$drawerCount} drawers × 4 pieces = " . ($drawerCount * 4) . " pieces)",
                'specs' => [
                    'width' => '1/4"',
                    'width_inches' => 0.25,
                    'depth' => '1/4"',
                    'depth_inches' => 0.25,
                    'position' => '1/2" from bottom edge',
                    'position_inches' => 0.5,
                ],
                'notes' => 'Cut dado in all 4 pieces (2 sides + front + back) before assembly. Bottom panel floats in dado.',
                'sequence' => 1,
            ];
        }

        // ========================================
        // BLUM TANDEM 563H RUNNER MOUNTING (Cabinet Sides)
        // From Blum Installation Instructions INST-TDM563H-563
        // ========================================
        if ($drawerCount > 0) {
            $operations['runner_mounting'] = [
                'operation' => 'Blum Runner Mounting Holes',
                'tool' => 'TANDEM template T65.1600.01 + drill',
                'applies_to' => "Cabinet sides (Left + Right) - {$drawerCount} runner pairs",
                'specs' => [
                    'height_from_bottom' => [
                        'mm' => 37,
                        'inches' => 1.46875,
                        'fraction' => '1-15/32"',
                        'note' => 'Measured from bottom of opening to bottom of runner',
                    ],
                    'setback_from_face' => [
                        'mm' => 3,
                        'inches' => 0.125,
                        'fraction' => '1/8"',
                        'note' => 'Frameless: runner flush with face; Face-frame: behind frame',
                    ],
                    'front_hole_positions' => [
                        ['mm' => 7, 'inches' => 0.28125, 'fraction' => '9/32"'],
                        ['mm' => 32, 'inches' => 1.25, 'fraction' => '1-1/4"'],
                    ],
                    'screw' => [
                        'size' => '#6 x 5/8"',
                        'blum_part' => '606N or 606P',
                    ],
                ],
                'notes' => 'Use at least 2 screws in elongated holes. Mark height line across BOTH sides for alignment.',
                'sequence' => 2,
            ];
        }

        // ========================================
        // BLUM LOCKING DEVICE HOLES (Drawer Box Sides)
        // Pre-bore for quick drawer attachment
        // ========================================
        if ($drawerCount > 0) {
            $operations['locking_device_holes'] = [
                'operation' => 'Locking Device Bores (Drawer Sides)',
                'tool' => '6mm brad point bit with depth stop',
                'applies_to' => "Drawer box sides ({$drawerCount} drawers × 2 sides = " . ($drawerCount * 2) . " bores)",
                'specs' => [
                    'bore_diameter' => [
                        'mm' => 6,
                        'inches' => 0.25,
                        'fraction' => '1/4" (use 6mm)',
                    ],
                    'bore_depth' => [
                        'mm' => 10,
                        'inches' => 0.40625,
                        'fraction' => '13/32"',
                    ],
                    'angle' => '75° (use TANDEM template T65.1600.01)',
                    'position' => 'Per template - front bottom of drawer side',
                    'locking_device_parts' => [
                        'T51.1901R/L' => 'With side-to-side adjustment',
                        'T51.1801R/L' => 'Without adjustment (basic)',
                    ],
                ],
                'notes' => 'Bore on OUTSIDE face of drawer sides. Use template for accurate 75° angle location.',
                'sequence' => 3,
            ];
        }

        // ========================================
        // BLUM REAR HOOK HOLES (Drawer Box Back)
        // For rear attachment to runner
        // ========================================
        if ($drawerCount > 0) {
            $operations['rear_hook_holes'] = [
                'operation' => 'Rear Hook Bores (Drawer Back)',
                'tool' => '2.5mm bit with extension chuck',
                'applies_to' => "Drawer backs ({$drawerCount} drawers × 2 holes = " . ($drawerCount * 2) . " bores)",
                'specs' => [
                    'position_from_bottom' => [
                        'mm' => 7,
                        'inches' => 0.28125,
                        'fraction' => '9/32"',
                    ],
                    'position_from_side' => [
                        'mm' => 11,
                        'inches' => 0.4375,
                        'fraction' => '7/16"',
                    ],
                    'minimum_rear_notch' => [
                        'mm' => 35,
                        'inches' => 1.375,
                        'fraction' => '1-3/8"',
                        'note' => 'Drawer back must clear rear bracket',
                    ],
                ],
                'notes' => 'Bore on OUTSIDE face of drawer back. One hole each side, measured from bottom corner.',
                'sequence' => 4,
            ];
        }

        // ========================================
        // SHELF PIN HOLES (Cabinet Sides)
        // ========================================
        // Only if cabinet has adjustable shelves (not sink base)
        $cabinetType = $specs['cabinet_type'] ?? 'base';
        $hasAdjustableShelves = !in_array($cabinetType, ['sink_base', 'kitchen_sink', 'vanity_sink']);

        if ($hasAdjustableShelves) {
            $insideDepth = $gate1['outputs']['inside_depth'];
            $boxHeight = $gate1['outputs']['box_height'];

            // Determine number of columns (add center at 28"+ depth)
            $columnCount = $insideDepth >= 28 ? 3 : 2;
            $columns = [
                ['position' => 'Front', 'setback' => '2"', 'setback_inches' => 2.0],
                ['position' => 'Back', 'setback' => '2" from back', 'setback_inches' => 2.0],
            ];
            if ($columnCount === 3) {
                $columns[] = ['position' => 'Center', 'setback' => $this->formatInches($insideDepth / 2), 'setback_inches' => $insideDepth / 2];
            }

            // Calculate number of holes per column (2" spacing, starting ~4" from bottom)
            $startHeight = 4.0; // Start 4" from bottom
            $endHeight = $boxHeight - 4.0; // Stop 4" from top
            $holesPerColumn = floor(($endHeight - $startHeight) / 2) + 1;

            $operations['shelf_pin_holes'] = [
                'operation' => 'Drill Shelf Pin Holes',
                'tool' => '5mm brad point drill bit',
                'applies_to' => 'Both cabinet sides (Left + Right)',
                'specs' => [
                    'hole_diameter' => '5mm (0.197")',
                    'hole_diameter_inches' => 0.1969,
                    'hole_depth' => '3/8"',
                    'hole_depth_inches' => 0.375,
                    'vertical_spacing' => '2"',
                    'vertical_spacing_inches' => 2.0,
                    'columns' => $columns,
                    'column_count' => $columnCount,
                    'holes_per_column' => $holesPerColumn,
                    'total_holes_per_side' => $holesPerColumn * $columnCount,
                    'total_holes' => $holesPerColumn * $columnCount * 2, // Both sides
                ],
                'notes' => "Drill {$columnCount} columns of holes on INSIDE face of each side panel. Use drilling jig for consistency.",
                'sequence' => 5,
            ];
        }

        // ========================================
        // EDGE BANDING
        // ========================================
        $operations['edge_banding'] = [
            'operation' => 'Edge Banding',
            'tool' => 'Iron-on edge banding or PVC edge bander',
            'applies_to' => 'Exposed plywood edges',
            'specs' => [
                'width' => '3/4" (match plywood thickness)',
                'material' => 'Match cabinet interior finish',
            ],
            'edges_to_band' => [
                'Cabinet sides' => 'Front edge only (visible in opening)',
                'Shelves' => 'Front edge only',
                'Face frame' => 'Not required (solid wood/MDF)',
                'Drawer boxes' => 'Top edge of sides (optional)',
            ],
            'notes' => 'Apply edge banding before assembly. Trim flush with edge trimmer.',
            'sequence' => 6,
        ];

        // ========================================
        // POCKET HOLES (Face Frame)
        // ========================================
        $operations['pocket_holes'] = [
            'operation' => 'Pocket Hole Drilling',
            'tool' => 'Kreg pocket hole jig (3/4" material setting)',
            'applies_to' => 'Face frame rails',
            'specs' => [
                'hole_size' => 'Standard (3/4" material)',
                'screw_size' => '1-1/4" fine thread pocket screws',
                'holes_per_rail' => 2,
            ],
            'notes' => 'Drill pocket holes in ends of ALL rails before assembly. Stiles receive the screws.',
            'sequence' => 7,
        ];

        return $operations;
    }

    /**
     * Generate cabinet box cut list (3/4" plywood)
     *
     * TCS CONSTRUCTION RULES (Bryan Patton, Jan 2025):
     * - Normal base: Sides are 3/4" shorter (stretchers sit ON TOP)
     * - Sink base: NO stretchers, sides go FULL box height
     * - Back covers full box height (used to square up the box)
     */
    protected function generateBoxCutList(array $specs, array $gate1): array
    {
        $insideWidth = $gate1['outputs']['inside_width'];
        $boxHeight = $gate1['outputs']['box_height'];
        $insideDepth = $gate1['outputs']['inside_depth'];
        $sidePanelHeight = $gate1['outputs']['side_panel_height'];  // Use calculated value from Gate 1
        $isSinkBase = $gate1['outputs']['is_sink_base'] ?? false;

        $sideNotes = $isSinkBase
            ? 'Depth × Box Height (full - no stretchers)'
            : 'Depth × (Box Height - 3/4") - stretchers on top';

        return [
            'material' => '3/4" Plywood',
            'pieces' => [
                [
                    'part' => 'Left Side',
                    'qty' => 1,
                    'width' => $insideDepth,
                    'width_formatted' => $this->formatInches($insideDepth),
                    'length' => $sidePanelHeight,
                    'length_formatted' => $this->formatInches($sidePanelHeight),
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => $sideNotes,
                ],
                [
                    'part' => 'Right Side',
                    'qty' => 1,
                    'width' => $insideDepth,
                    'width_formatted' => $this->formatInches($insideDepth),
                    'length' => $sidePanelHeight,
                    'length_formatted' => $this->formatInches($sidePanelHeight),
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => $sideNotes,
                ],
                [
                    'part' => 'Bottom',
                    'qty' => 1,
                    'width' => $insideWidth,
                    'width_formatted' => $this->formatInches($insideWidth),
                    'length' => $insideDepth,
                    'length_formatted' => $this->formatInches($insideDepth),
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Inside Width × Depth',
                ],
                [
                    'part' => 'Back',
                    'qty' => 1,
                    'width' => $insideWidth,
                    'width_formatted' => $this->formatInches($insideWidth),
                    'length' => $boxHeight,
                    'length_formatted' => $this->formatInches($boxHeight),
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'TCS: Back is FULL BOX HEIGHT (toe kick is front only)',
                ],
            ],
        ];
    }

    /**
     * Generate stretcher cut list (3/4" plywood)
     */
    protected function generateStretcherCutList(array $gate5): array
    {
        $pieces = [];

        foreach ($gate5['stretchers'] as $stretcher) {
            $pieces[] = [
                'part' => "{$stretcher['position']} Stretcher #{$stretcher['number']}",
                'qty' => 1,
                'width' => $stretcher['depth'],
                'width_formatted' => $this->formatInches($stretcher['depth']),
                'length' => $stretcher['width'],
                'length_formatted' => $this->formatInches($stretcher['width']),
                'thickness' => $stretcher['thickness'],
                'notes' => 'TCS 3" standard',
            ];
        }

        return [
            'material' => '3/4" Plywood',
            'pieces' => $pieces,
        ];
    }

    /**
     * Generate false front cut list with backing pieces
     *
     * TCS Rule (Bryan Patton, Jan 2025):
     * - False front FACE: decorative panel attached to backing
     * - False front BACKING: 3/4" plywood, laid FLAT (horizontal), spans cabinet width
     * - Backing doubles as stretcher - provides structural support
     *
     * Cut Orientation for BACKING:
     * - WIDTH = inside cabinet width (spans left-to-right)
     * - DEPTH = backing height (3" standard, front-to-back)
     * - Laid HORIZONTALLY like a shelf/stretcher
     */
    protected function generateFalseFrontCutList(array $specs, array $gate1): array
    {
        $falseFronts = $specs['false_fronts'] ?? [];
        $pieces = [];

        if (empty($falseFronts)) {
            return [
                'material' => '3/4" Plywood',
                'pieces' => [],
                'notes' => 'No false fronts in this cabinet',
            ];
        }

        $insideWidth = $gate1['outputs']['inside_width'];
        $ffNumber = 0;

        foreach ($falseFronts as $ff) {
            $ffNumber++;

            // FALSE FRONT FACE (decorative panel)
            $faceHeight = $ff['face_height'] ?? 7;
            $pieces[] = [
                'part' => "False Front #{$ffNumber} Face",
                'qty' => 1,
                'width' => $faceHeight,
                'width_formatted' => $this->formatInches($faceHeight),
                'length' => $specs['width'], // Full cabinet width (covers face frame)
                'length_formatted' => $this->formatInches($specs['width']),
                'thickness' => self::PLYWOOD_3_4,
                'notes' => 'Decorative face - full overlay',
            ];

            // FALSE FRONT BACKING (structural piece that doubles as stretcher)
            if ($ff['has_backing'] ?? true) {
                $backingHeight = $ff['backing_height'] ?? 3; // 3" standard depth (front-to-back)
                $pieces[] = [
                    'part' => "False Front #{$ffNumber} Backing",
                    'qty' => 1,
                    'width' => $backingHeight, // Depth (front-to-back direction)
                    'width_formatted' => $this->formatInches($backingHeight),
                    'length' => $insideWidth, // Spans cabinet inside width
                    'length_formatted' => $this->formatInches($insideWidth),
                    'thickness' => $ff['backing_thickness'] ?? self::PLYWOOD_3_4,
                    'notes' => ($ff['backing_is_stretcher'] ?? true)
                        ? 'Laid FLAT - doubles as stretcher (TCS rule)'
                        : 'Laid FLAT - horizontal orientation',
                    'orientation' => 'horizontal', // Critical: laid flat like a shelf
                    'is_stretcher' => $ff['backing_is_stretcher'] ?? true,
                ];
            }
        }

        return [
            'material' => '3/4" Plywood',
            'pieces' => $pieces,
            'tcs_rule' => 'All false fronts have backing that doubles as stretcher',
        ];
    }

    /**
     * Generate face frame cut list (5/4 hardwood = 1" actual thickness)
     * TCS Standard: Face frames are 5/4 hardwood, NOT 3/4" sheet goods
     */
    protected function generateFaceFrameCutList(array $gate6): array
    {
        $pieces = [];

        foreach ($gate6['pieces'] as $type => $piece) {
            $partName = ucwords(str_replace('_', ' ', $type));
            $pieces[] = [
                'part' => $partName,
                'qty' => $piece['qty'],
                'width' => $piece['width'],
                'width_formatted' => $this->formatInches($piece['width']),
                'length' => $piece['length'],
                'length_formatted' => $this->formatInches($piece['length']),
                'thickness' => self::HARDWOOD_5_4,  // 5/4 hardwood = 1" actual
                'notes' => $piece['notes'],
            ];
        }

        return [
            'material' => '5/4 Hardwood (1" actual)',  // TCS Standard
            'pieces' => $pieces,
        ];
    }

    /**
     * Generate toe kick cut list (3/4" plywood)
     */
    protected function generateToeKickCutList(array $specs, array $gate1): array
    {
        $insideWidth = $gate1['outputs']['inside_width'];
        $toeKickHeight = $specs['toe_kick_height'];

        return [
            'material' => '3/4" Plywood',
            'pieces' => [
                [
                    'part' => 'Toe Kick Board',
                    'qty' => 1,
                    'width' => $toeKickHeight,
                    'width_formatted' => $this->formatInches($toeKickHeight),
                    'length' => $insideWidth,
                    'length_formatted' => $this->formatInches($insideWidth),
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Recessed 3" from face',
                ],
            ],
        ];
    }

    /**
     * Generate end panel cut list (decorative panels mitered to face frame)
     *
     * TCS RULES (from Brian/Levi Jan 2025):
     * - End panels are mitered at 45° to face frame stiles
     * - End panels get +0.5" overage for install adjustment (walls are never plumb)
     * - 1/4" gap between cabinet side and end panel
     * - End panels ship attached to cabinet, shimmed and screwed
     */
    protected function generateEndPanelCutList(array $specs, array $gate1): array
    {
        $hasEndPanels = $specs['has_end_panels'] ?? false;

        if (!$hasEndPanels) {
            return [
                'material' => '3/4" Plywood or Hardwood',
                'pieces' => [],
                'note' => 'No end panels specified for this cabinet',
            ];
        }

        $cabinetHeight = $specs['height'];
        $cabinetDepth = $specs['depth'];
        $toeKickHeight = $specs['toe_kick_height'];

        // End panel dimensions
        // Height: Full cabinet height (includes toe kick area)
        $endPanelHeight = $cabinetHeight;

        // Depth: Cabinet depth + install overage
        $installOverage = self::END_PANEL_INSTALL_OVERAGE;  // +0.5" for wall adjustment
        $endPanelDepth = $cabinetDepth + $installOverage;

        // Determine how many end panels (left, right, or both)
        $endPanelLocations = $specs['end_panel_locations'] ?? ['left', 'right'];  // Default both
        $pieces = [];

        foreach ($endPanelLocations as $location) {
            $pieces[] = [
                'part' => ucfirst($location) . ' End Panel',
                'qty' => 1,
                'width' => $endPanelDepth,
                'width_formatted' => $this->formatInches($endPanelDepth),
                'length' => $endPanelHeight,
                'length_formatted' => $this->formatInches($endPanelHeight),
                'thickness' => self::PLYWOOD_3_4,
                'notes' => "Depth includes +{$installOverage}\" install overage. Miter 45° to face frame.",
                'miter' => '45° to face frame stile',
                'install_overage' => $installOverage,
            ];
        }

        return [
            'material' => '3/4" Plywood or Hardwood (match job specs)',
            'pieces' => $pieces,
            'tcs_rules' => [
                'Mitered at 45° to face frame stiles',
                'Include +0.5" overage for install adjustment',
                '1/4" gap between cabinet side and end panel',
                'Ships attached - shimmed and screwed',
            ],
        ];
    }

    /**
     * Generate drawer box cut list (1/2" plywood sides, 1/4" bottom)
     */
    protected function generateDrawerBoxCutList(array $gate4): array
    {
        $pieces = [];

        foreach ($gate4['drawer_boxes'] as $drawer) {
            $num = $drawer['drawer_number'];
            $outputs = $drawer['outputs'];

            // Drawer sides (1/2" plywood)
            $pieces[] = [
                'part' => "Drawer {$num} Sides",
                'qty' => 2,
                'width' => $outputs['box_height_shop'],
                'width_formatted' => $this->formatInches($outputs['box_height_shop']),
                'length' => $outputs['box_depth_shop'],
                'length_formatted' => $this->formatInches($outputs['box_depth_shop']),
                'thickness' => self::PLYWOOD_1_2,
                'notes' => 'Shop depth (with dado allowance)',
            ];

            // Drawer front/back (1/2" plywood)
            // Width deducts 2× side thickness
            $frontBackWidth = $outputs['box_width'] - (2 * self::PLYWOOD_1_2);
            $pieces[] = [
                'part' => "Drawer {$num} Front/Back",
                'qty' => 2,
                'width' => $outputs['box_height_shop'],
                'width_formatted' => $this->formatInches($outputs['box_height_shop']),
                'length' => $frontBackWidth,
                'length_formatted' => $this->formatInches($frontBackWidth),
                'thickness' => self::PLYWOOD_1_2,
                'notes' => 'Box width - 2× side thickness',
            ];

            // Drawer bottom (1/4" plywood, in dado)
            // Width = box width + dado insets
            $bottomWidth = $outputs['box_width'] - (2 * 0.375); // Allow for 3/8" dado on each side
            $bottomLength = $outputs['box_depth'] - (2 * 0.375);
            $pieces[] = [
                'part' => "Drawer {$num} Bottom",
                'qty' => 1,
                'width' => $bottomWidth,
                'width_formatted' => $this->formatInches($bottomWidth),
                'length' => $bottomLength,
                'length_formatted' => $this->formatInches($bottomLength),
                'thickness' => self::PLYWOOD_1_4,
                'notes' => 'In dado groove',
            ];
        }

        return [
            'material' => '1/2" Plywood (sides), 1/4" Plywood (bottom)',
            'pieces' => $pieces,
        ];
    }

    // =========================================================================
    // SUMMARY & UTILITIES
    // =========================================================================

    /**
     * Build cabinet identification for Rhino/V-Carve export
     *
     * Extracts project_number, cabinet_number, and full_code from Cabinet model
     * or generates defaults for array input.
     *
     * @param Cabinet|array $input Cabinet model or specs array
     * @return array Cabinet identification data
     */
    protected function buildCabinetIdentification(Cabinet|array $input): array
    {
        if ($input instanceof Cabinet) {
            // Get project through relationship chain
            $project = $input->project
                ?? $input->room?->project
                ?? $input->cabinetRun?->roomLocation?->room?->project;

            $projectNumber = $project?->project_number ?? 'TCS-000-Unknown';
            $cabinetNumber = $input->cabinet_number ?? 'CAB-' . $input->id;
            $fullCode = $input->full_code ?? $input->generateFullCode();

            // Use TcsMaterialService for short code generation
            $materialService = app(TcsMaterialService::class);
            $shortCode = $materialService->getShortProjectCode($projectNumber);
            $cabinetId = $materialService->buildCabinetId($projectNumber, $cabinetNumber);

            return [
                'cabinet_id' => $cabinetId,           // AUST-BTH1-B1-C1 (for Rhino)
                'project_number' => $projectNumber,   // TCS-001-9AustinFarmRoad
                'cabinet_number' => $cabinetNumber,   // BTH1-B1-C1
                'full_code' => $fullCode,             // TCS-001-9AustinFarmRoad-BTH1-SW-B1
                'short_project_code' => $shortCode,   // AUST
            ];
        }

        // Array input - generate defaults
        $projectCode = $input['project_code'] ?? 'TCS';
        $cabinetNumber = $input['cabinet_number'] ?? 'CAB-001';

        return [
            'cabinet_id' => $projectCode . '-' . $cabinetNumber,
            'project_number' => $input['project_number'] ?? $projectCode,
            'cabinet_number' => $cabinetNumber,
            'full_code' => $input['full_code'] ?? $projectCode . '-' . $cabinetNumber,
            'short_project_code' => $projectCode,
        ];
    }

    /**
     * Generate audit summary
     */
    protected function generateSummary(array $gate3, array $gapAssessment): array
    {
        $issues = [];
        $warnings = [];

        // Check component fit
        if (!($gate3['outputs']['status_ok'] ?? true)) {
            $issues[] = 'Components do not fit in opening - ' . abs($gate3['outputs']['remaining_space']) . '" overflow';
        }

        // Check depth
        foreach ($gapAssessment['cabinet_depth'] ?? [] as $check) {
            if ($check['status'] === 'AT MIN') {
                $warnings[] = 'Cabinet depth at minimum for slide - OK but tight';
            } elseif ($check['status'] === 'INSUFFICIENT') {
                $issues[] = 'Cabinet depth insufficient for drawer slide';
            }
        }

        return [
            'status' => empty($issues) ? 'PASS' : 'FAIL',
            'issues' => $issues,
            'warnings' => $warnings,
            'component_fit' => $gate3['outputs']['fit_status'] ?? 'UNKNOWN',
            'remaining_space' => $gate3['outputs']['remaining_space'] ?? 0,
        ];
    }

    /**
     * Format inches with fractional display
     *
     * @param float $inches Decimal inches
     * @return string Formatted string (e.g., "41-5/16\"")
     */
    public function formatInches(float $inches): string
    {
        $whole = floor($inches);
        $fraction = $inches - $whole;

        if ($fraction < 0.03125) {
            // Less than 1/32, just whole number
            return $whole . '"';
        }

        // Convert to 16ths
        $sixteenths = round($fraction * 16);

        if ($sixteenths == 0) {
            return $whole . '"';
        }

        if ($sixteenths == 16) {
            return ($whole + 1) . '"';
        }

        // Reduce fraction
        $numerator = $sixteenths;
        $denominator = 16;

        while ($numerator % 2 == 0 && $denominator > 1) {
            $numerator /= 2;
            $denominator /= 2;
        }

        if ($whole == 0) {
            return "{$numerator}/{$denominator}\"";
        }

        return "{$whole}-{$numerator}/{$denominator}\"";
    }

    /**
     * Format audit as console output (for artisan commands)
     */
    public function formatConsoleOutput(array $audit): string
    {
        $output = [];
        $output[] = str_repeat('=', 70);
        $output[] = 'CABINET MATH AUDIT - GATES OF CONSTRUCTION';
        $output[] = str_repeat('=', 70);
        $output[] = '';

        // Input Specs
        $output[] = 'INPUT SPECIFICATIONS:';
        $specs = $audit['input_specs'];
        $output[] = sprintf("  Width:      %s", $this->formatInches($specs['width']));
        $output[] = sprintf("  Height:     %s", $this->formatInches($specs['height']));
        $output[] = sprintf("  Depth:      %s", $this->formatInches($specs['depth']));
        $output[] = sprintf("  Toe Kick:   %s", $this->formatInches($specs['toe_kick_height']));
        $output[] = sprintf("  FF Stile:   %s", $this->formatInches($specs['face_frame_stile']));
        $output[] = sprintf("  FF Rail:    %s", $this->formatInches($specs['face_frame_rail']));
        $output[] = sprintf("  Drawers:    %d", count($specs['drawer_heights']));
        $output[] = '';

        // Each gate
        foreach ($audit['gates'] as $key => $gate) {
            $output[] = str_repeat('-', 70);
            $output[] = sprintf("GATE %d: %s", $gate['gate'], strtoupper($gate['title']));
            $output[] = str_repeat('-', 70);

            // Standard calculations
            if (isset($gate['calculations'])) {
                foreach ($gate['calculations'] as $calc) {
                    $output[] = sprintf("  %s:", $calc['name']);
                    if (isset($calc['formula'])) {
                        $output[] = sprintf("    Formula: %s", $calc['formula']);
                    }
                    if (isset($calc['values'])) {
                        $output[] = sprintf("    Values:  %s", $calc['values']);
                    }
                    $output[] = sprintf("    Result:  %s", $calc['result_formatted'] ?? $calc['result']);
                    $output[] = '';
                }
            }

            // Gate 4 special handling for drawer boxes
            if (isset($gate['drawer_boxes'])) {
                foreach ($gate['drawer_boxes'] as $drawer) {
                    $output[] = sprintf("  DRAWER %d (Opening: %s):",
                        $drawer['drawer_number'],
                        $this->formatInches($drawer['opening_height'])
                    );
                    foreach ($drawer['calculations'] as $calc) {
                        $output[] = sprintf("    %s:", $calc['name']);
                        if (isset($calc['formula'])) {
                            $output[] = sprintf("      Formula: %s", $calc['formula']);
                        }
                        $output[] = sprintf("      Result:  %s", $calc['result_formatted'] ?? $calc['result']);
                    }
                    $output[] = '';
                }

                // Depth validation
                if (isset($gate['depth_validation'])) {
                    $dv = $gate['depth_validation'];
                    $output[] = sprintf("  Depth Validation:");
                    $output[] = sprintf("    Min Required: %s", $this->formatInches($dv['min_required']));
                    $output[] = sprintf("    Actual:       %s", $this->formatInches($dv['actual']));
                    $output[] = sprintf("    Status:       %s", $dv['status']);
                    $output[] = '';
                }
            }
        }

        // Summary
        $output[] = str_repeat('=', 70);
        $output[] = 'SUMMARY';
        $output[] = str_repeat('=', 70);
        $summary = $audit['summary'];
        $output[] = sprintf("  Status: %s", $summary['status']);
        $output[] = sprintf("  Component Fit: %s", $summary['component_fit']);
        $output[] = sprintf("  Remaining Space: %s", $this->formatInches($summary['remaining_space']));

        if (!empty($summary['warnings'])) {
            $output[] = '';
            $output[] = '  WARNINGS:';
            foreach ($summary['warnings'] as $warning) {
                $output[] = "    ⚠️  {$warning}";
            }
        }

        if (!empty($summary['issues'])) {
            $output[] = '';
            $output[] = '  ISSUES:';
            foreach ($summary['issues'] as $issue) {
                $output[] = "    ❌  {$issue}";
            }
        }

        return implode("\n", $output);
    }

    // =========================================================================
    // 3D POSITION SYSTEM
    // Reference: Front-Top-Left corner of cabinet as origin (0,0,0)
    //
    // COORDINATE SYSTEM (Right-Hand Rule per CNC standards):
    //   X-axis: Left → Right (positive)
    //   Y-axis: Top → Bottom (positive, matches SVG and CAD conventions)
    //   Z-axis: Front → Back (positive)
    //
    // Each part has:
    //   - Position (x, y, z): Location of front-top-left corner of the part
    //   - Dimensions (w, h, d): Width (X), Height (Y), Depth (Z)
    //   - Orientation: rotation, grain_direction, face_up for CNC
    //   - CNC: finished_ends, edgebanding, machining_notes
    //
    // Reference: CNC Coordinate Systems (Autodesk Fusion Blog, BobsCNC)
    //            Mozaik Nesting Parameters (GrainMatch, FinishedEnds)
    // =========================================================================

    /**
     * Generate 3D position data for all cabinet parts
     *
     * @param array $specs Normalized input specifications
     * @param array $gate1 Gate 1 (Cabinet Box) results
     * @param array $gate4 Gate 4 (Drawer Clearances) results
     * @param array $gate5 Gate 5 (Stretchers) results
     * @param array $gate6 Gate 6 (Face Frame) results
     * @return array Complete 3D position map for all parts
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
        // CABINET BOX PARTS
        // ========================================

        // ========================================
        // COORDINATE SYSTEM: Y=0 at BOTTOM of box
        // Y increases upward (Y-up system)
        // Toe kick is in NEGATIVE Y (below box)
        // ========================================

        // LEFT SIDE PANEL - sits on Y=0, extends up to boxH
        $parts['left_side'] = [
            'part_name' => 'Left Side',
            'part_type' => 'cabinet_box',
            'position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'dimensions' => ['w' => $sideThickness, 'h' => $boxH, 'd' => $cabD],
            'cut_dimensions' => ['width' => $cabD, 'length' => $boxH, 'thickness' => $sideThickness],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'vertical',
                'face_up' => 'outside', // Outside face up on CNC
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => ['front'],
                'machining' => ['shelf_pin_holes', 'runner_mounting'],
            ],
            'material' => '3/4" Plywood',
        ];

        // RIGHT SIDE PANEL
        $parts['right_side'] = [
            'part_name' => 'Right Side',
            'part_type' => 'cabinet_box',
            'position' => ['x' => $cabW - $sideThickness, 'y' => 0, 'z' => 0],
            'dimensions' => ['w' => $sideThickness, 'h' => $boxH, 'd' => $cabD],
            'cut_dimensions' => ['width' => $cabD, 'length' => $boxH, 'thickness' => $sideThickness],
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
        ];

        // BOTTOM PANEL - sits at Y=0 (bottom of box)
        $parts['bottom'] = [
            'part_name' => 'Bottom',
            'part_type' => 'cabinet_box',
            'position' => ['x' => $sideThickness, 'y' => 0, 'z' => 0],
            'dimensions' => ['w' => $insideW, 'h' => $sideThickness, 'd' => $cabD - $backThickness],
            'cut_dimensions' => ['width' => $cabD - $backThickness, 'length' => $insideW, 'thickness' => $sideThickness],
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
        ];

        // BACK PANEL
        $parts['back'] = [
            'part_name' => 'Back',
            'part_type' => 'cabinet_box',
            'position' => ['x' => $sideThickness, 'y' => 0, 'z' => $cabD - $backThickness],
            'dimensions' => ['w' => $insideW, 'h' => $boxH, 'd' => $backThickness],
            'cut_dimensions' => ['width' => $insideW, 'length' => $boxH, 'thickness' => $backThickness],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'vertical',
                'face_up' => 'back', // Inside face up on CNC
            ],
            'cnc' => [
                'finished_ends' => 'none',
                'edgebanding' => [],
                'machining' => [],
            ],
            'material' => '3/4" Plywood',
        ];

        // TOE KICK (recessed)
        $parts['toe_kick'] = [
            'part_name' => 'Toe Kick',
            'part_type' => 'cabinet_box',
            'position' => ['x' => $sideThickness, 'y' => $boxH, 'z' => $toeKickRecess],
            'dimensions' => ['w' => $insideW, 'h' => $toeKick, 'd' => $sideThickness],
            'cut_dimensions' => ['width' => $insideW, 'length' => $toeKick, 'thickness' => $sideThickness],
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
            'notes' => "Recessed {$toeKickRecess}\" from front face",
        ];

        // ========================================
        // FACE FRAME PARTS
        // ========================================

        // LEFT STILE
        $parts['left_stile'] = [
            'part_name' => 'Left Stile',
            'part_type' => 'face_frame',
            'position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'dimensions' => ['w' => $ffStile, 'h' => $boxH, 'd' => self::PLYWOOD_3_4],
            'cut_dimensions' => ['width' => $ffStile, 'length' => $boxH, 'thickness' => self::PLYWOOD_3_4],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'vertical',
                'face_up' => 'front',
            ],
            'cnc' => [
                'finished_ends' => 'both',
                'edgebanding' => ['front', 'back'],
                'machining' => [],
            ],
            'material' => '3/4" Hardwood',
        ];

        // RIGHT STILE
        $parts['right_stile'] = [
            'part_name' => 'Right Stile',
            'part_type' => 'face_frame',
            'position' => ['x' => $cabW - $ffStile, 'y' => 0, 'z' => 0],
            'dimensions' => ['w' => $ffStile, 'h' => $boxH, 'd' => self::PLYWOOD_3_4],
            'cut_dimensions' => ['width' => $ffStile, 'length' => $boxH, 'thickness' => self::PLYWOOD_3_4],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'vertical',
                'face_up' => 'front',
            ],
            'cnc' => [
                'finished_ends' => 'both',
                'edgebanding' => ['front', 'back'],
                'machining' => [],
            ],
            'material' => '3/4" Hardwood',
        ];

        // TOP RAIL
        $openingW = $cabW - (2 * $ffStile);
        $parts['top_rail'] = [
            'part_name' => 'Top Rail',
            'part_type' => 'face_frame',
            'position' => ['x' => $ffStile, 'y' => 0, 'z' => 0],
            'dimensions' => ['w' => $openingW, 'h' => $ffRail, 'd' => self::PLYWOOD_3_4],
            'cut_dimensions' => ['width' => $ffRail, 'length' => $openingW, 'thickness' => self::PLYWOOD_3_4],
            'orientation' => [
                'rotation' => 0,
                'grain_direction' => 'horizontal',
                'face_up' => 'front',
            ],
            'cnc' => [
                'finished_ends' => 'none', // Joins to stiles
                'edgebanding' => ['front', 'back'],
                'machining' => [],
            ],
            'material' => '3/4" Hardwood',
        ];

        // BOTTOM RAIL
        $parts['bottom_rail'] = [
            'part_name' => 'Bottom Rail',
            'part_type' => 'face_frame',
            'position' => ['x' => $ffStile, 'y' => $boxH - $ffRail, 'z' => 0],
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
        // Stack from top going down
        //
        // In full overlay:
        // - Top of first component overlaps the top rail by (rail_width - gap)
        // - Each component has a 1/8" gap between it and the next
        // - Bottom of last component overlaps the bottom rail
        // ========================================
        $topOverlap = $ffRail - $gap; // How much the top face overlaps the rail
        $currentY = $gap; // Start at the top reveal (1/8" below top of box)
        $falseFronts = $specs['false_fronts'] ?? [];
        $drawerHeights = $specs['drawer_heights'] ?? [];

        // False fronts (at top)
        foreach ($falseFronts as $idx => $ff) {
            $ffNum = $idx + 1;
            $faceH = $ff['face_height'] ?? 7;

            // FALSE FRONT FACE
            $parts["false_front_{$ffNum}_face"] = [
                'part_name' => "False Front #{$ffNum} Face",
                'part_type' => 'false_front',
                'position' => ['x' => $ffStile, 'y' => $currentY, 'z' => 0],
                'dimensions' => ['w' => $openingW, 'h' => $faceH, 'd' => self::PLYWOOD_3_4],
                'cut_dimensions' => ['width' => $faceH, 'length' => $cabW, 'thickness' => self::PLYWOOD_3_4],
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
                $parts["false_front_{$ffNum}_backing"] = [
                    'part_name' => "False Front #{$ffNum} Backing",
                    'part_type' => 'false_front_backing',
                    'position' => ['x' => $sideThickness, 'y' => $currentY + ($faceH - $backingH) / 2, 'z' => self::PLYWOOD_3_4],
                    'dimensions' => ['w' => $insideW, 'h' => $backingH, 'd' => self::PLYWOOD_3_4],
                    'cut_dimensions' => ['width' => $backingH, 'length' => $insideW, 'thickness' => self::PLYWOOD_3_4],
                    'orientation' => [
                        'rotation' => 90, // Laid FLAT (horizontal)
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

            $currentY += $faceH + $gap;

            // Mid rail after false front (positioned BEHIND the overlapping faces)
            // In full overlay, the mid rail's TOP aligns with where the face frame
            // opening divider would be if there was no overlay
            $midRailY = $ffRail + ($idx + 1) * ($faceH + $gap) - ($gap / 2) - ($ffRail / 2);
            $parts["mid_rail_ff_{$ffNum}"] = [
                'part_name' => "Mid Rail (after FF#{$ffNum})",
                'part_type' => 'face_frame',
                'position' => ['x' => $ffStile, 'y' => $midRailY, 'z' => 0],
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
            // Note: Don't add rail height to currentY - faces overlay the rails
        }

        // Drawers (upper first, then lower)
        $drawerBoxes = $gate4['drawer_boxes'] ?? [];
        foreach ($drawerHeights as $idx => $drawerFaceH) {
            $drawerNum = $idx + 1;
            $drawerType = ($idx === 0 && !empty($falseFronts)) ? 'u_drawer' : 'drawer';
            $drawerLabel = $drawerType === 'u_drawer' ? 'U-Shaped Drawer' : 'Drawer';

            // DRAWER FACE
            $parts["drawer_{$drawerNum}_face"] = [
                'part_name' => "{$drawerLabel} #{$drawerNum} Face",
                'part_type' => 'drawer_face',
                'position' => ['x' => $ffStile, 'y' => $currentY, 'z' => 0],
                'dimensions' => ['w' => $openingW, 'h' => $drawerFaceH, 'd' => self::PLYWOOD_3_4],
                'cut_dimensions' => ['width' => $drawerFaceH, 'length' => $cabW, 'thickness' => self::PLYWOOD_3_4],
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
                $boxH = $box['outputs']['box_height_shop'] ?? $box['outputs']['box_height_exact'] ?? 0;
                $boxD = $box['outputs']['box_depth_shop'] ?? $box['outputs']['box_depth'] ?? 18;

                // Drawer slides position (37mm from bottom of opening)
                $slideHeight = 1.46875; // 37mm in inches
                $drawerBoxY = $currentY + $drawerFaceH - $boxH - self::BLUM_BOTTOM_CLEARANCE;

                // Drawer box X position: centered on cabinet (same center as drawer face)
                // The drawer box is narrower than the face opening by the Blum side deduction
                // But it must be centered so the face attaches properly to the front
                $drawerBoxX = ($cabW - $boxW) / 2;

                // Drawer box Z position: starts at rear of drawer face and goes back
                // Drawer face is at Z=0 with thickness 0.75", so box starts at Z=0.75"
                $drawerBoxZ = self::PLYWOOD_3_4;  // Behind the drawer face

                $parts["drawer_{$drawerNum}_box"] = [
                    'part_name' => "{$drawerLabel} #{$drawerNum} Box",
                    'part_type' => 'drawer_box',
                    'position' => [
                        'x' => $drawerBoxX,
                        'y' => $drawerBoxY,
                        'z' => $drawerBoxZ,
                    ],
                    'dimensions' => ['w' => $boxW, 'h' => $boxH, 'd' => $boxD],
                    'box_parts' => [
                        'sides' => ['qty' => 2, 'width' => $boxH, 'length' => $boxD, 'thickness' => self::PLYWOOD_1_2],
                        'front_back' => ['qty' => 2, 'width' => $boxH, 'length' => $boxW - (2 * self::PLYWOOD_1_2), 'thickness' => self::PLYWOOD_1_2],
                        'bottom' => ['qty' => 1, 'width' => $boxW - 0.125, 'length' => $boxD - 0.5, 'thickness' => self::PLYWOOD_1_4],
                    ],
                    'orientation' => [
                        'rotation' => 0,
                        'grain_direction' => 'none', // Drawer box is assembled
                        'face_up' => 'n/a',
                    ],
                    'cnc' => [
                        'finished_ends' => 'none',
                        'edgebanding' => ['top'], // Top of sides only
                        'machining' => ['dado_1_4', 'locking_device_holes', 'rear_hook_holes'],
                    ],
                    'material' => '1/2" Plywood (sides), 1/4" (bottom)',
                ];
            }

            $currentY += $drawerFaceH + $gap;

            // Mid rail between drawers (not after last one)
            // In full overlay, the mid rail is centered where the gap between faces is
            if ($idx < count($drawerHeights) - 1) {
                // Calculate mid rail position: centered at the gap between faces
                $midRailDrawerY = $currentY - $gap - ($ffRail / 2);
                $parts["mid_rail_drawer_{$drawerNum}"] = [
                    'part_name' => "Mid Rail (after Drawer #{$drawerNum})",
                    'part_type' => 'face_frame',
                    'position' => ['x' => $ffStile, 'y' => $midRailDrawerY, 'z' => 0],
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
                // Note: Don't add rail height to currentY in full overlay
            }
        }

        // ========================================
        // STRETCHERS (excluding false front backings which serve as stretchers)
        // ========================================
        $stretcherDepth = $gate5['outputs']['stretcher_depth'] ?? 3.0;
        $stretcherThickness = $gate5['outputs']['stretcher_thickness'] ?? self::PLYWOOD_3_4;

        // Front stretcher (at top of box, behind face frame)
        $parts['front_stretcher'] = [
            'part_name' => 'Front Stretcher',
            'part_type' => 'stretcher',
            'position' => ['x' => $sideThickness, 'y' => 0, 'z' => self::PLYWOOD_3_4], // Behind face frame
            'dimensions' => ['w' => $insideW, 'h' => $stretcherThickness, 'd' => $stretcherDepth],
            'cut_dimensions' => ['width' => $stretcherDepth, 'length' => $insideW, 'thickness' => $stretcherThickness],
            'orientation' => [
                'rotation' => 90, // Laid flat
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

        // Back stretcher (at top of box, against back)
        $parts['back_stretcher'] = [
            'part_name' => 'Back Stretcher',
            'part_type' => 'stretcher',
            'position' => ['x' => $sideThickness, 'y' => 0, 'z' => $cabD - $backThickness - $stretcherDepth],
            'dimensions' => ['w' => $insideW, 'h' => $stretcherThickness, 'd' => $stretcherDepth],
            'cut_dimensions' => ['width' => $stretcherDepth, 'length' => $insideW, 'thickness' => $stretcherThickness],
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

        return [
            'coordinate_system' => [
                'origin' => 'Front-Top-Left corner of cabinet',
                'x_axis' => 'Left → Right (positive)',
                'y_axis' => 'Top → Bottom (positive)',
                'z_axis' => 'Front → Back (positive)',
                'units' => 'inches',
            ],
            'cabinet_envelope' => [
                'width' => $cabW,
                'height' => $cabH,
                'depth' => $cabD,
            ],
            'parts' => $parts,
            'part_count' => count($parts),
        ];
    }

    /**
     * Generate HTML report for the audit
     *
     * @param array $audit Full audit data from generateFullAudit()
     * @param string|null $projectName Optional project name override
     * @param string|null $cabinetCode Optional cabinet code
     * @return string Rendered HTML
     */
    public function generateHtmlReport(array $audit, ?string $projectName = null, ?string $cabinetCode = null, bool $includeNesting = true): string
    {
        $audit['project_name'] = $projectName ?? $audit['project_name'] ?? 'Cabinet Specification';
        $audit['cabinet_code'] = $cabinetCode ?? $audit['cabinet_code'] ?? null;
        $audit['generated_at'] = now()->format('F j, Y \a\t g:i A');

        // Pass the formatInches function to the view
        $formatInches = fn($value) => $this->formatInches($value);

        // Generate sheet nesting if requested and cut list exists
        $nesting = null;
        if ($includeNesting && !empty($audit['cut_list'])) {
            $nestingService = app(SheetNestingService::class);
            $nesting = $nestingService->nestCutList($audit['cut_list']);
        }

        return view('reports.cabinet-math-audit', [
            'audit' => $audit,
            'formatInches' => $formatInches,
            'nesting' => $nesting,
        ])->render();
    }

    /**
     * Generate HTML report and save to file
     *
     * @param array $audit Full audit data from generateFullAudit()
     * @param string $outputPath Path to save HTML file
     * @param string|null $projectName Optional project name override
     * @param string|null $cabinetCode Optional cabinet code
     * @return string The path where file was saved
     */
    public function generateHtmlReportToFile(array $audit, string $outputPath, ?string $projectName = null, ?string $cabinetCode = null): string
    {
        $html = $this->generateHtmlReport($audit, $projectName, $cabinetCode);

        file_put_contents($outputPath, $html);

        return $outputPath;
    }

    // =========================================================================
    // CONSTRUCTION VALIDATION - "Things Must Touch and Add Up"
    // =========================================================================

    /**
     * Validate construction consistency - ensure all parts touch and add up
     *
     * TCS Standard: Every dimension must be traceable and verifiable.
     * "Things must be touching and add up" - Bryan Patton
     *
     * @param array $audit Full audit data from generateFullAudit()
     * @return array Validation results with errors, warnings, and verification
     */
    public function validateConstructionConsistency(array $audit): array
    {
        $errors = [];
        $warnings = [];
        $verifications = [];
        $specs = $audit['input_specs'];
        $parts = $audit['positions_3d']['parts'] ?? [];
        $tolerance = 0.001; // 1/1000" tolerance for floating point comparison

        // ===== VALIDATION 1: Cabinet envelope dimensions =====
        $envelope = $audit['positions_3d']['cabinet_envelope'] ?? [];

        // Width must equal: left_side + inside_width + right_side
        $leftSide = $parts['left_side']['dimensions']['w'] ?? 0;
        $rightSide = $parts['right_side']['dimensions']['w'] ?? 0;
        $insideW = $audit['gates']['gate_1_cabinet_box']['outputs']['inside_width'] ?? 0;
        $calculatedWidth = $leftSide + $insideW + $rightSide;

        if (abs($calculatedWidth - $specs['width']) > $tolerance) {
            $errors[] = [
                'check' => 'Width adds up',
                'expected' => $specs['width'],
                'calculated' => $calculatedWidth,
                'formula' => "left_side ({$leftSide}) + inside_width ({$insideW}) + right_side ({$rightSide})",
                'difference' => $calculatedWidth - $specs['width'],
            ];
        } else {
            $verifications[] = [
                'check' => 'Width adds up',
                'value' => $specs['width'],
                'formula' => "left_side ({$leftSide}) + inside_width ({$insideW}) + right_side ({$rightSide}) = {$calculatedWidth}",
                'status' => '✅ PASS',
            ];
        }

        // ===== VALIDATION 2: Height must add up =====
        // Total height = toe_kick + box_height (or countertop if included)
        $toeKickH = $specs['toe_kick_height'] ?? 0;
        $boxH = $audit['gates']['gate_1_cabinet_box']['outputs']['box_height'] ?? 0;
        $calculatedHeight = $toeKickH + $boxH;

        if (abs($calculatedHeight - $specs['height']) > $tolerance) {
            $errors[] = [
                'check' => 'Height adds up',
                'expected' => $specs['height'],
                'calculated' => $calculatedHeight,
                'formula' => "toe_kick ({$toeKickH}) + box_height ({$boxH})",
                'difference' => $calculatedHeight - $specs['height'],
            ];
        } else {
            $verifications[] = [
                'check' => 'Height adds up',
                'value' => $specs['height'],
                'formula' => "toe_kick ({$toeKickH}) + box_height ({$boxH}) = {$calculatedHeight}",
                'status' => '✅ PASS',
            ];
        }

        // ===== VALIDATION 3: Depth must add up =====
        // Inside depth + back_thickness = cabinet depth
        $insideD = $audit['gates']['gate_1_cabinet_box']['outputs']['inside_depth'] ?? 0;
        $backThickness = $specs['back_panel_thickness'] ?? self::PLYWOOD_3_4;
        $calculatedDepth = $insideD + $backThickness;

        if (abs($calculatedDepth - $specs['depth']) > $tolerance) {
            $errors[] = [
                'check' => 'Depth adds up',
                'expected' => $specs['depth'],
                'calculated' => $calculatedDepth,
                'formula' => "inside_depth ({$insideD}) + back_thickness ({$backThickness})",
                'difference' => $calculatedDepth - $specs['depth'],
            ];
        } else {
            $verifications[] = [
                'check' => 'Depth adds up',
                'value' => $specs['depth'],
                'formula' => "inside_depth ({$insideD}) + back_thickness ({$backThickness}) = {$calculatedDepth}",
                'status' => '✅ PASS',
            ];
        }

        // ===== VALIDATION 4: Face frame opening width =====
        $stileW = $specs['face_frame_stile'] ?? 1.5;
        $ffOpeningW = $audit['gates']['gate_2_face_frame_opening']['outputs']['opening_width'] ?? 0;
        $calculatedFFWidth = $stileW + $ffOpeningW + $stileW;

        if (abs($calculatedFFWidth - $specs['width']) > $tolerance) {
            $errors[] = [
                'check' => 'Face frame width adds up',
                'expected' => $specs['width'],
                'calculated' => $calculatedFFWidth,
                'formula' => "left_stile ({$stileW}) + opening ({$ffOpeningW}) + right_stile ({$stileW})",
                'difference' => $calculatedFFWidth - $specs['width'],
            ];
        } else {
            $verifications[] = [
                'check' => 'Face frame width adds up',
                'value' => $specs['width'],
                'formula' => "left_stile ({$stileW}) + opening ({$ffOpeningW}) + right_stile ({$stileW}) = {$calculatedFFWidth}",
                'status' => '✅ PASS',
            ];
        }

        // ===== VALIDATION 5: Face frame opening height =====
        $railW = $specs['face_frame_rail'] ?? 1.5;
        $ffOpeningH = $audit['gates']['gate_2_face_frame_opening']['outputs']['opening_height'] ?? 0;
        $calculatedFFHeight = $railW + $ffOpeningH + $railW;

        if (abs($calculatedFFHeight - $boxH) > $tolerance) {
            $errors[] = [
                'check' => 'Face frame height adds up',
                'expected' => $boxH,
                'calculated' => $calculatedFFHeight,
                'formula' => "top_rail ({$railW}) + opening ({$ffOpeningH}) + bottom_rail ({$railW})",
                'difference' => $calculatedFFHeight - $boxH,
            ];
        } else {
            $verifications[] = [
                'check' => 'Face frame height adds up',
                'value' => $boxH,
                'formula' => "top_rail ({$railW}) + opening ({$ffOpeningH}) + bottom_rail ({$railW}) = {$calculatedFFHeight}",
                'status' => '✅ PASS',
            ];
        }

        // ===== VALIDATION 6: Parts touch at correct positions =====
        // Left side starts at X=0
        if (isset($parts['left_side'])) {
            $leftX = $parts['left_side']['position']['x'] ?? -1;
            if ($leftX != 0) {
                $errors[] = [
                    'check' => 'Left side starts at X=0',
                    'expected' => 0,
                    'actual' => $leftX,
                ];
            } else {
                $verifications[] = [
                    'check' => 'Left side starts at X=0',
                    'value' => $leftX,
                    'status' => '✅ PASS',
                ];
            }
        }

        // Right side ends at cabinet width
        if (isset($parts['right_side'])) {
            $rightX = $parts['right_side']['position']['x'] ?? 0;
            $rightW = $parts['right_side']['dimensions']['w'] ?? 0;
            $rightEnd = $rightX + $rightW;
            if (abs($rightEnd - $specs['width']) > $tolerance) {
                $errors[] = [
                    'check' => 'Right side ends at cabinet width',
                    'expected' => $specs['width'],
                    'actual' => $rightEnd,
                    'formula' => "position_x ({$rightX}) + width ({$rightW})",
                ];
            } else {
                $verifications[] = [
                    'check' => 'Right side ends at cabinet width',
                    'value' => $rightEnd,
                    'status' => '✅ PASS',
                ];
            }
        }

        // Back panel is against back of cabinet
        if (isset($parts['back'])) {
            $backZ = $parts['back']['position']['z'] ?? 0;
            $backD = $parts['back']['dimensions']['d'] ?? 0;
            $backEnd = $backZ + $backD;
            if (abs($backEnd - $specs['depth']) > $tolerance) {
                $errors[] = [
                    'check' => 'Back panel ends at cabinet depth',
                    'expected' => $specs['depth'],
                    'actual' => $backEnd,
                ];
            } else {
                $verifications[] = [
                    'check' => 'Back panel ends at cabinet depth',
                    'value' => $backEnd,
                    'status' => '✅ PASS',
                ];
            }
        }

        // ===== VALIDATION 7: Drawer box fits in opening =====
        $drawerGate = $audit['gates']['gate_4_drawer_clearances'] ?? null;
        if ($drawerGate && isset($drawerGate['outputs']['drawers'])) {
            foreach ($drawerGate['outputs']['drawers'] as $idx => $drawer) {
                $boxWidth = $drawer['box_width'] ?? 0;
                // Box width + slide clearances should = opening width
                $expectedWidth = $ffOpeningW - self::BLUM_SIDE_DEDUCTION;
                if (abs($boxWidth - $expectedWidth) > $tolerance) {
                    $warnings[] = [
                        'check' => "Drawer " . ($idx + 1) . " box width",
                        'expected' => $expectedWidth,
                        'actual' => $boxWidth,
                        'note' => 'Box width should = opening - slide clearance',
                    ];
                } else {
                    $verifications[] = [
                        'check' => "Drawer " . ($idx + 1) . " box width fits",
                        'value' => $boxWidth,
                        'formula' => "opening ({$ffOpeningW}) - slide_clearance (" . self::BLUM_SIDE_DEDUCTION . ") = {$expectedWidth}",
                        'status' => '✅ PASS',
                    ];
                }
            }
        }

        // ===== SUMMARY =====
        $isValid = count($errors) === 0;

        return [
            'valid' => $isValid,
            'status' => $isValid ? '✅ ALL CONSTRUCTION MATH VERIFIED' : '❌ CONSTRUCTION ERRORS FOUND',
            'error_count' => count($errors),
            'warning_count' => count($warnings),
            'verification_count' => count($verifications),
            'errors' => $errors,
            'warnings' => $warnings,
            'verifications' => $verifications,
            'summary' => [
                'cabinet_dimensions' => [
                    'width' => $specs['width'],
                    'height' => $specs['height'],
                    'depth' => $specs['depth'],
                ],
                'box_dimensions' => [
                    'height' => $boxH,
                    'inside_width' => $insideW,
                    'inside_depth' => $insideD,
                ],
                'face_frame' => [
                    'opening_width' => $ffOpeningW,
                    'opening_height' => $ffOpeningH,
                    'stile_width' => $stileW,
                    'rail_width' => $railW,
                ],
            ],
        ];
    }
}
