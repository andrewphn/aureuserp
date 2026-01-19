<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\ConstructionTemplate;

/**
 * Cabinet Calculator Service - Simple Input to Full Output
 *
 * Takes exterior dimensions + component list and calculates everything:
 * - Box dimensions with formulas
 * - Face frame openings
 * - Component dimensions (drawers, false fronts)
 * - Stretcher positions
 * - Complete cut list
 *
 * TCS CONSTRUCTION STANDARDS (Bryan Patton, Jan 2025-2026):
 * - Base cabinets: 34.75" height (+ 1.25" countertop = 36")
 * - Toe kick: 4.5" height, 3" recess (configurable)
 * - Face frame: 1.5" stiles/rails, 1/8" door gap
 * - Stretchers: 3" depth, count = 2 + drawers - FF backings
 * - Sink cabinets: No stretchers, sides +0.75" extension
 * - Back wall gap: 0.25" for safety
 * - False front backing = stretcher (dual purpose)
 *
 * @see docs/construction-standards/tcs-cabinet-formulas.md
 */
class CabinetCalculatorService
{
    /**
     * Blum TANDEM 563H constants (1/2" drawer sides)
     */
    public const BLUM_SIDE_DEDUCTION = 0.625;      // 5/8" total width deduction
    public const BLUM_HEIGHT_DEDUCTION = 0.8125;   // 13/16" total height deduction
    public const DRAWER_SIDE_THICKNESS = 0.5;       // 1/2" plywood sides
    public const DRAWER_BOTTOM_THICKNESS = 0.25;    // 1/4" plywood bottom

    /**
     * TCS Standard defaults (can be overridden by ConstructionTemplate)
     */
    public const DEFAULT_TOE_KICK_HEIGHT = 4.5;
    public const DEFAULT_TOE_KICK_RECESS = 3.0;
    public const DEFAULT_STILE_WIDTH = 1.5;
    public const DEFAULT_RAIL_WIDTH = 1.5;
    public const DEFAULT_DOOR_GAP = 0.125;
    public const DEFAULT_COMPONENT_GAP = 0.125;
    public const DEFAULT_STRETCHER_DEPTH = 3.0;
    public const DEFAULT_MATERIAL_THICKNESS = 0.75;
    public const DEFAULT_BACK_WALL_GAP = 0.5;  // 1/2" gap between cabinet back and wall
    public const DEFAULT_SINK_SIDE_EXTENSION = 0.75;
    public const DEFAULT_SLIDE_LENGTH = 18;
    public const DEFAULT_FF_BACKING_OVERHANG = 1.0;

    protected ?ConstructionStandardsService $standards = null;
    protected ?DrawerConfiguratorService $drawerCalc = null;

    public function __construct(
        ?ConstructionStandardsService $standards = null,
        ?DrawerConfiguratorService $drawerCalc = null
    ) {
        $this->standards = $standards ?? app(ConstructionStandardsService::class);
        $this->drawerCalc = $drawerCalc ?? app(DrawerConfiguratorService::class);
    }

    /**
     * Main entry point - calculate everything from simple exterior dimensions
     *
     * @param array $input Input specification
     * @return array Complete calculation output with formulas
     *
     * Example input:
     * [
     *     'exterior' => ['width' => 41.3125, 'height' => 32.75, 'depth' => 21],
     *     'toe_kick_height' => 4.0,
     *     'cabinet_type' => 'sink_base',
     *     'components' => [
     *         ['type' => 'false_front', 'height' => 6.0],
     *         ['type' => 'drawer', 'height' => 10.0],
     *         ['type' => 'drawer', 'height' => 10.0],
     *     ],
     * ]
     */
    public function calculateFromExterior(array $input): array
    {
        // 1. Extract and validate input
        $exterior = $input['exterior'] ?? [];
        $cabinetWidth = $exterior['width'] ?? 0;
        $cabinetHeight = $exterior['height'] ?? 0;
        $cabinetDepth = $exterior['depth'] ?? 0;

        $cabinetType = $input['cabinet_type'] ?? 'base';
        $components = $input['components'] ?? [];

        // 2. Get construction standards (from template or defaults)
        $standards = $this->resolveStandards($input);

        // 2b. Check if cabinet has drawers and validate depth
        $hasDrawers = $this->componentsHaveDrawers($components);
        $drawerDepthValidation = null;

        if ($hasDrawers) {
            $slideLength = $standards['values']['drawer_slide_length'] ?? self::DEFAULT_SLIDE_LENGTH;
            $drawerDepthValidation = $this->validateCabinetDepthForDrawers(
                $cabinetDepth,
                $slideLength,
                $standards
            );

            // If depth is insufficient and auto_adjust is enabled, use the required depth
            if (!$drawerDepthValidation['is_sufficient'] && ($input['auto_adjust_depth'] ?? false)) {
                $originalDepth = $cabinetDepth;
                $cabinetDepth = $drawerDepthValidation['required_depth'];

                // Re-validate with adjusted depth and note the adjustment
                $drawerDepthValidation = $this->validateCabinetDepthForDrawers(
                    $cabinetDepth,
                    $slideLength,
                    $standards
                );
                $drawerDepthValidation['auto_adjusted'] = true;
                $drawerDepthValidation['original_depth'] = $originalDepth;
                $drawerDepthValidation['adjusted_depth'] = $cabinetDepth;
            }
        }

        // 3. Calculate box dimensions
        $box = $this->calculateBoxDimensions($cabinetWidth, $cabinetHeight, $cabinetDepth, $cabinetType, $standards);

        // 4. Calculate face frame dimensions
        $faceFrame = $this->calculateFaceFrameDimensions($cabinetWidth, $box['height']['value'], $standards);

        // 5. Calculate component dimensions
        $componentCalcs = $this->calculateComponents($components, $faceFrame, $box, $standards);

        // 6. Calculate stretchers
        $stretchers = $this->calculateStretchers($components, $box, $cabinetDepth, $cabinetType, $standards);

        // 7. Generate cut list
        $cutList = $this->generateCutList($box, $faceFrame, $componentCalcs, $stretchers, $standards);

        // 8. Validate
        $validation = $this->validate($box, $faceFrame, $componentCalcs);

        // 8b. Add drawer depth validation to overall validation
        if ($drawerDepthValidation) {
            $validation['drawer_depth'] = $drawerDepthValidation;
            if (!$drawerDepthValidation['is_sufficient']) {
                $validation['errors'][] = $drawerDepthValidation['message'];
            }
        }

        return [
            'input_summary' => [
                'exterior' => [
                    'width' => $this->formatDimension($cabinetWidth),
                    'height' => $this->formatDimension($cabinetHeight),
                    'depth' => $this->formatDimension($cabinetDepth),
                ],
                'cabinet_type' => $cabinetType,
                'component_count' => count($components),
                'has_drawers' => $hasDrawers,
            ],
            'construction_standards' => $standards,
            'calculations' => [
                'box' => $box,
                'face_frame' => $faceFrame,
                'components' => $componentCalcs,
                'stretchers' => $stretchers,
            ],
            'cut_list' => $cutList,
            'validation' => $validation,
        ];
    }

    /**
     * Check if components array includes any drawers
     */
    protected function componentsHaveDrawers(array $components): bool
    {
        foreach ($components as $component) {
            $type = $component['type'] ?? '';
            if (in_array($type, ['drawer', 'drawer_bank', 'drawers'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Resolve construction standards from template or defaults
     */
    protected function resolveStandards(array $input): array
    {
        // Get template if specified
        $templateId = $input['construction_template_id'] ?? null;
        $template = $templateId ? ConstructionTemplate::find($templateId) : ConstructionTemplate::getDefault();

        // Build standards array with source tracking
        return [
            'source' => $template ? $template->name : 'TCS Defaults (Fallback)',
            'template_id' => $template?->id,
            'values' => [
                'toe_kick_height' => $input['toe_kick_height'] ?? $template?->toe_kick_height ?? self::DEFAULT_TOE_KICK_HEIGHT,
                'toe_kick_recess' => $template?->toe_kick_recess ?? self::DEFAULT_TOE_KICK_RECESS,
                'face_frame_stile_width' => $input['face_frame_stile'] ?? $template?->face_frame_stile_width ?? self::DEFAULT_STILE_WIDTH,
                'face_frame_rail_width' => $input['face_frame_rail'] ?? $template?->face_frame_rail_width ?? self::DEFAULT_RAIL_WIDTH,
                'face_frame_door_gap' => $template?->face_frame_door_gap ?? self::DEFAULT_DOOR_GAP,
                'component_gap' => self::DEFAULT_COMPONENT_GAP,
                'stretcher_depth' => $template?->stretcher_depth ?? self::DEFAULT_STRETCHER_DEPTH,
                'stretcher_thickness' => $template?->stretcher_thickness ?? self::DEFAULT_MATERIAL_THICKNESS,
                'box_material_thickness' => $template?->box_material_thickness ?? self::DEFAULT_MATERIAL_THICKNESS,
                'back_panel_thickness' => $template?->back_panel_thickness ?? self::DEFAULT_MATERIAL_THICKNESS,
                'side_panel_thickness' => $template?->side_panel_thickness ?? self::DEFAULT_MATERIAL_THICKNESS,
                'back_wall_gap' => $template?->back_wall_gap ?? self::DEFAULT_BACK_WALL_GAP,
                'sink_side_extension' => $template?->sink_side_extension ?? self::DEFAULT_SINK_SIDE_EXTENSION,
                'drawer_slide_length' => $input['drawer_slide_length'] ?? self::DEFAULT_SLIDE_LENGTH,
            ],
        ];
    }

    /**
     * Calculate the minimum required cabinet depth based on drawer slide requirements.
     *
     * TOTAL DEPTH FORMULA (front of face frame to wall):
     * Total = Face Frame + Drawer + Clearance + Back + Wall Gap
     * 21"   = 1.25"      + 18"    + 0.75"     + 0.75" + 0.25"
     *
     * Components (front to back):
     * 1. Face frame depth (stile thickness): typically 1.25" or 0.75"
     * 2. Drawer depth (slide length): 18", 21", 15", 12", or 9"
     * 3. Clearance behind drawer: 0.75" (shop practice)
     * 4. Back material thickness: 0.75"
     * 5. Wall gap (scribe/clearance): 0.25"
     *
     * @param int $slideLength Drawer slide length in inches (9, 12, 15, 18, or 21)
     * @param array $standards Construction standards array
     * @return array Depth requirements with formulas
     */
    public function calculateRequiredCabinetDepth(int $slideLength, array $standards): array
    {
        $s = $standards['values'];

        // Face frame depth (stile thickness)
        $faceFrameDepth = $s['face_frame_stile_width'] ?? self::DEFAULT_STILE_WIDTH;

        // Drawer depth = slide length
        $drawerDepth = $slideLength;

        // Clearance behind drawer (shop practice)
        $clearance = DrawerConfiguratorService::SHOP_MIN_DEPTH_ADDITION;

        // Back panel thickness
        $backThickness = $s['back_panel_thickness'] ?? self::DEFAULT_MATERIAL_THICKNESS;

        // Wall gap for clearance/scribing
        $backGap = $s['back_wall_gap'] ?? self::DEFAULT_BACK_WALL_GAP;

        // Internal depth (side panel depth) = drawer + clearance
        $internalDepth = $drawerDepth + $clearance;

        // Total cabinet depth = face frame + drawer + clearance + back + gap
        $requiredTotalDepth = $faceFrameDepth + $drawerDepth + $clearance + $backThickness + $backGap;

        // Blum's official minimum (for reference)
        $blumMinDepth = DrawerConfiguratorService::BLUM_MIN_CABINET_DEPTHS[$slideLength]
            ?? ($slideLength + 0.90625);

        return [
            'slide_length' => $slideLength,
            'components' => [
                'face_frame_depth' => $faceFrameDepth,
                'drawer_depth' => $drawerDepth,
                'clearance_behind_drawer' => $clearance,
                'back_thickness' => $backThickness,
                'back_wall_gap' => $backGap,
            ],
            'internal_depth' => [
                'value' => $internalDepth,
                'formula' => sprintf(
                    'Drawer + Clearance = %d" + %.4f" = %.4f"',
                    $drawerDepth,
                    $clearance,
                    $internalDepth
                ),
                'blum_reference' => $blumMinDepth,
            ],
            'required_cabinet_depth' => [
                'value' => $requiredTotalDepth,
                'formula' => sprintf(
                    'Face Frame + Drawer + Clearance + Back + Gap = %.4f" + %d" + %.4f" + %.4f" + %.4f" = %.4f"',
                    $faceFrameDepth,
                    $drawerDepth,
                    $clearance,
                    $backThickness,
                    $backGap,
                    $requiredTotalDepth
                ),
            ],
        ];
    }

    /**
     * Calculate internal depth available from a given total cabinet depth.
     *
     * Works backwards: Total depth to wall - gap - back = internal depth
     *
     * @param float $totalDepth Total cabinet depth to wall
     * @param array $standards Construction standards
     * @return array Internal depth and available drawer space
     */
    public function calculateInternalDepthFromTotal(float $totalDepth, array $standards): array
    {
        $s = $standards['values'];

        $backThickness = $s['back_panel_thickness'] ?? self::DEFAULT_MATERIAL_THICKNESS;
        $backGap = $s['back_wall_gap'] ?? self::DEFAULT_BACK_WALL_GAP;

        // Internal depth = total - gap - back
        $internalDepth = $totalDepth - $backGap - $backThickness;

        // Available for drawer = internal - clearance
        $shopClearance = DrawerConfiguratorService::SHOP_MIN_DEPTH_ADDITION;
        $availableForDrawer = $internalDepth - $shopClearance;

        // What slide fits?
        $slideLengths = [21, 18, 15, 12, 9];
        $maxSlide = null;
        foreach ($slideLengths as $length) {
            if ($availableForDrawer >= $length) {
                $maxSlide = $length;
                break;
            }
        }

        return [
            'total_depth_to_wall' => $totalDepth,
            'back_wall_gap' => $backGap,
            'back_thickness' => $backThickness,
            'internal_depth' => [
                'value' => $internalDepth,
                'formula' => sprintf(
                    'Total - Gap - Back = %.4f" - %.4f" - %.4f" = %.4f"',
                    $totalDepth,
                    $backGap,
                    $backThickness,
                    $internalDepth
                ),
            ],
            'available_for_drawer' => [
                'value' => $availableForDrawer,
                'formula' => sprintf(
                    'Internal - Clearance = %.4f" - %.4f" = %.4f"',
                    $internalDepth,
                    $shopClearance,
                    $availableForDrawer
                ),
            ],
            'max_slide_length' => $maxSlide,
            'slide_options' => array_map(function ($length) use ($availableForDrawer) {
                return [
                    'length' => $length,
                    'fits' => $availableForDrawer >= $length,
                    'clearance' => round($availableForDrawer - $length, 4),
                ];
            }, $slideLengths),
        ];
    }

    /**
     * Validate and optionally adjust cabinet depth for drawer requirements.
     *
     * Call this when configuring a drawer cabinet to ensure the depth is sufficient.
     * Returns validation result and recommended depth if current is insufficient.
     *
     * @param float $currentDepth Current cabinet depth
     * @param int $slideLength Drawer slide length required
     * @param array $standards Construction standards
     * @return array Validation result with recommendation
     */
    public function validateCabinetDepthForDrawers(
        float $currentDepth,
        int $slideLength,
        array $standards
    ): array {
        $required = $this->calculateRequiredCabinetDepth($slideLength, $standards);
        $requiredDepth = $required['required_cabinet_depth']['value'];

        $isSufficient = $currentDepth >= $requiredDepth;
        $shortage = $isSufficient ? 0 : ($requiredDepth - $currentDepth);

        return [
            'is_sufficient' => $isSufficient,
            'current_depth' => $currentDepth,
            'required_depth' => $requiredDepth,
            'shortage' => $shortage,
            'slide_length' => $slideLength,
            'min_internal_depth' => $required['internal_depth']['value'],
            'message' => $isSufficient
                ? sprintf('Cabinet depth (%.4f") is sufficient for %d" drawer slides.', $currentDepth, $slideLength)
                : sprintf(
                    'Cabinet depth (%.4f") is %.4f" short for %d" drawer slides. Required: %.4f"',
                    $currentDepth,
                    $shortage,
                    $slideLength,
                    $requiredDepth
                ),
            'recommendation' => $isSufficient ? null : [
                'action' => 'increase_depth',
                'recommended_depth' => $requiredDepth,
                'alternative' => $slideLength > 9
                    ? sprintf('Or use shorter %d" slides (requires %.4f" depth)',
                        $slideLength - 3,
                        $this->calculateRequiredCabinetDepth($slideLength - 3, $standards)['required_cabinet_depth']['value'])
                    : null,
            ],
            'calculation_details' => $required,
        ];
    }

    /**
     * Determine the appropriate slide length for a given cabinet depth.
     *
     * Works in reverse - given a cabinet depth, what's the longest slide that fits?
     *
     * @param float $cabinetDepth Cabinet depth in inches
     * @param array $standards Construction standards
     * @return array Recommended slide length and details
     */
    public function getMaxSlideForCabinetDepth(float $cabinetDepth, array $standards): array
    {
        $s = $standards['values'];
        $backThickness = $s['back_panel_thickness'] ?? self::DEFAULT_MATERIAL_THICKNESS;
        $backGap = $s['back_wall_gap'] ?? self::DEFAULT_BACK_WALL_GAP;

        // Available internal depth
        $internalDepth = $cabinetDepth - $backThickness - $backGap;

        // Available for slide (minus shop clearance)
        $availableForSlide = $internalDepth - DrawerConfiguratorService::SHOP_MIN_DEPTH_ADDITION;

        // Standard slide lengths
        $slideLengths = [21, 18, 15, 12, 9];
        $recommendedSlide = null;

        foreach ($slideLengths as $length) {
            if ($availableForSlide >= $length) {
                $recommendedSlide = $length;
                break;
            }
        }

        return [
            'cabinet_depth' => $cabinetDepth,
            'internal_depth' => $internalDepth,
            'available_for_slide' => $availableForSlide,
            'recommended_slide_length' => $recommendedSlide,
            'formula' => sprintf(
                'Cabinet Depth - Back - Gap - Clearance = %.4f" - %.4f" - %.4f" - %.4f" = %.4f" available',
                $cabinetDepth,
                $backThickness,
                $backGap,
                DrawerConfiguratorService::SHOP_MIN_DEPTH_ADDITION,
                $availableForSlide
            ),
            'all_options' => array_map(function ($length) use ($availableForSlide) {
                return [
                    'slide_length' => $length,
                    'fits' => $availableForSlide >= $length,
                    'clearance' => $availableForSlide - $length,
                ];
            }, $slideLengths),
            'warning' => $recommendedSlide === null
                ? 'Cabinet depth is too shallow for any standard drawer slides. Minimum 9" slides require ~10.5" cabinet depth.'
                : null,
        ];
    }

    /**
     * Calculate box dimensions with formulas
     */
    protected function calculateBoxDimensions(
        float $cabinetWidth,
        float $cabinetHeight,
        float $cabinetDepth,
        string $cabinetType,
        array $standards
    ): array {
        $s = $standards['values'];
        $isSink = in_array($cabinetType, ['sink_base', 'vanity_sink', 'kitchen_sink']);

        // Box height = Cabinet height - Toe kick
        $boxHeight = $cabinetHeight - $s['toe_kick_height'];

        // Inside width = Cabinet width - (2 × side thickness)
        $insideWidth = $cabinetWidth - (2 * $s['side_panel_thickness']);

        // Inside depth = Cabinet depth - back thickness - back wall gap
        $insideDepth = $cabinetDepth - $s['back_panel_thickness'] - $s['back_wall_gap'];

        // Side panel height
        // Standard: reduced by stretcher thickness (sides sandwiched)
        // Sink: full height + extension (no stretchers)
        if ($isSink) {
            $sideHeight = $boxHeight + $s['sink_side_extension'];
            $sideFormula = sprintf(
                'Box Height + Sink Extension = %.4f + %.4f = %.4f"',
                $boxHeight, $s['sink_side_extension'], $sideHeight
            );
        } else {
            $sideHeight = $boxHeight - $s['stretcher_thickness'];
            $sideFormula = sprintf(
                'Box Height - Stretcher Thickness = %.4f - %.4f = %.4f"',
                $boxHeight, $s['stretcher_thickness'], $sideHeight
            );
        }

        return [
            'height' => [
                'value' => $boxHeight,
                'formula' => sprintf(
                    'Cabinet Height - Toe Kick = %.4f - %.4f = %.4f"',
                    $cabinetHeight, $s['toe_kick_height'], $boxHeight
                ),
            ],
            'inside_width' => [
                'value' => $insideWidth,
                'formula' => sprintf(
                    'Cabinet Width - (2 × Side Thickness) = %.4f - (2 × %.4f) = %.4f"',
                    $cabinetWidth, $s['side_panel_thickness'], $insideWidth
                ),
            ],
            'inside_depth' => [
                'value' => $insideDepth,
                'formula' => sprintf(
                    'Cabinet Depth - Back Thickness - Back Wall Gap = %.4f - %.4f - %.4f = %.4f"',
                    $cabinetDepth, $s['back_panel_thickness'], $s['back_wall_gap'], $insideDepth
                ),
            ],
            'side_height' => [
                'value' => $sideHeight,
                'formula' => $sideFormula,
                'note' => $isSink ? 'Sink cabinet: sides extend for countertop support' : 'Standard: sides reduced for stretcher sandwich',
            ],
            'back_height' => [
                'value' => $boxHeight,
                'formula' => sprintf('Back Height = Box Height = %.4f"', $boxHeight),
                'note' => 'TCS: Back is full box height (squares up the box)',
            ],
            'bottom_width' => [
                'value' => $insideWidth,
                'formula' => sprintf('Bottom Width = Inside Width = %.4f"', $insideWidth),
            ],
            'bottom_depth' => [
                'value' => $insideDepth,
                'formula' => sprintf('Bottom Depth = Inside Depth = %.4f"', $insideDepth),
            ],
            'is_sink' => $isSink,
        ];
    }

    /**
     * Calculate face frame dimensions with formulas
     */
    protected function calculateFaceFrameDimensions(float $cabinetWidth, float $boxHeight, array $standards): array
    {
        $s = $standards['values'];

        // Opening width = Cabinet width - (2 × stile width)
        $openingWidth = $cabinetWidth - (2 * $s['face_frame_stile_width']);

        // Opening height = Box height - (2 × rail width)
        $openingHeight = $boxHeight - (2 * $s['face_frame_rail_width']);

        // Stile length = Box height (full height)
        $stileLength = $boxHeight;

        // Rail length = Cabinet width - (2 × stile width)
        $railLength = $openingWidth;

        return [
            'opening_width' => [
                'value' => $openingWidth,
                'formula' => sprintf(
                    'Cabinet Width - (2 × Stile Width) = %.4f - (2 × %.4f) = %.4f"',
                    $cabinetWidth, $s['face_frame_stile_width'], $openingWidth
                ),
            ],
            'opening_height' => [
                'value' => $openingHeight,
                'formula' => sprintf(
                    'Box Height - (2 × Rail Width) = %.4f - (2 × %.4f) = %.4f"',
                    $boxHeight, $s['face_frame_rail_width'], $openingHeight
                ),
            ],
            'stile_length' => [
                'value' => $stileLength,
                'formula' => sprintf('Stile Length = Box Height = %.4f"', $stileLength),
            ],
            'rail_length' => [
                'value' => $railLength,
                'formula' => sprintf('Rail Length = Opening Width = %.4f"', $railLength),
            ],
            'stile_width' => $s['face_frame_stile_width'],
            'rail_width' => $s['face_frame_rail_width'],
            'door_gap' => $s['face_frame_door_gap'],
        ];
    }

    /**
     * Calculate component dimensions (false fronts, drawers, doors)
     */
    protected function calculateComponents(array $components, array $faceFrame, array $box, array $standards): array
    {
        $s = $standards['values'];
        $openingWidth = $faceFrame['opening_width']['value'];
        $slideLength = $s['drawer_slide_length'];

        $calcs = [];
        $drawerIndex = 0;
        $falseFrontIndex = 0;

        foreach ($components as $index => $component) {
            $type = $component['type'] ?? 'unknown';
            $faceHeight = $component['height'] ?? 6.0;

            if ($type === 'false_front') {
                $falseFrontIndex++;
                $calcs[] = $this->calculateFalseFront($falseFrontIndex, $faceHeight, $openingWidth, $s);
            } elseif ($type === 'drawer') {
                $drawerIndex++;
                $shape = $component['shape'] ?? 'standard';
                $calcs[] = $this->calculateDrawer($drawerIndex, $faceHeight, $openingWidth, $slideLength, $shape, $s);
            } elseif ($type === 'door') {
                $calcs[] = $this->calculateDoor($index + 1, $faceHeight, $openingWidth, $faceFrame, $s);
            }
        }

        return $calcs;
    }

    /**
     * Calculate false front dimensions
     */
    protected function calculateFalseFront(int $index, float $faceHeight, float $openingWidth, array $s): array
    {
        // Panel dimensions (with gap)
        $panelWidth = $openingWidth - (2 * $s['face_frame_door_gap']);
        $panelHeight = $faceHeight - (2 * $s['face_frame_door_gap']);

        // Backing dimensions (TCS: backing = stretcher)
        $backingWidth = $openingWidth - (2 * $s['side_panel_thickness']);
        $backingHeight = $faceHeight + self::DEFAULT_FF_BACKING_OVERHANG;

        return [
            'type' => 'false_front',
            'index' => $index,
            'face_height' => $faceHeight,
            'panel' => [
                'width' => [
                    'value' => $panelWidth,
                    'formula' => sprintf(
                        'Opening Width - (2 × Door Gap) = %.4f - %.4f = %.4f"',
                        $openingWidth, 2 * $s['face_frame_door_gap'], $panelWidth
                    ),
                ],
                'height' => [
                    'value' => $panelHeight,
                    'formula' => sprintf(
                        'Face Height - (2 × Door Gap) = %.4f - %.4f = %.4f"',
                        $faceHeight, 2 * $s['face_frame_door_gap'], $panelHeight
                    ),
                ],
                'thickness' => $s['box_material_thickness'],
            ],
            'backing' => [
                'width' => [
                    'value' => $backingWidth,
                    'formula' => sprintf(
                        'Opening Width - (2 × Side Thickness) = %.4f - %.4f = %.4f"',
                        $openingWidth, 2 * $s['side_panel_thickness'], $backingWidth
                    ),
                ],
                'height' => [
                    'value' => $backingHeight,
                    'formula' => sprintf(
                        'Face Height + Overhang = %.4f + %.4f = %.4f"',
                        $faceHeight, self::DEFAULT_FF_BACKING_OVERHANG, $backingHeight
                    ),
                ],
                'thickness' => $s['box_material_thickness'],
                'note' => 'TCS Rule: Backing serves dual purpose as stretcher',
            ],
        ];
    }

    /**
     * Calculate drawer dimensions using Blum TANDEM 563H specs
     */
    protected function calculateDrawer(
        int $index,
        float $faceHeight,
        float $openingWidth,
        int $slideLength,
        string $shape,
        array $s
    ): array {
        // Front dimensions (with gap)
        $frontWidth = $openingWidth - (2 * $s['face_frame_door_gap']);
        $frontHeight = $faceHeight - (2 * $s['face_frame_door_gap']);

        // Box dimensions (Blum TANDEM 563H with 1/2" sides)
        $boxWidth = $openingWidth - self::BLUM_SIDE_DEDUCTION;
        $boxHeightExact = $faceHeight - self::BLUM_HEIGHT_DEDUCTION;
        $boxHeightShop = floor($boxHeightExact * 2) / 2; // Round down to 1/2"
        $boxDepth = $slideLength;
        $boxDepthShop = $slideLength + 0.25; // Shop practice: +1/4"

        // Piece dimensions for dovetail construction
        $sideLength = $boxDepthShop;
        $sideHeight = $boxHeightShop;
        $frontBackLength = $boxWidth - (2 * self::DRAWER_SIDE_THICKNESS);
        $bottomWidth = $frontBackLength + (2 * 0.25) - 0.0625; // In dado
        $bottomLength = $boxDepthShop - 0.75; // Inset from back

        return [
            'type' => 'drawer',
            'index' => $index,
            'shape' => $shape,
            'face_height' => $faceHeight,
            'front' => [
                'width' => [
                    'value' => $frontWidth,
                    'formula' => sprintf(
                        'Opening Width - (2 × Door Gap) = %.4f - %.4f = %.4f"',
                        $openingWidth, 2 * $s['face_frame_door_gap'], $frontWidth
                    ),
                ],
                'height' => [
                    'value' => $frontHeight,
                    'formula' => sprintf(
                        'Face Height - (2 × Door Gap) = %.4f - %.4f = %.4f"',
                        $faceHeight, 2 * $s['face_frame_door_gap'], $frontHeight
                    ),
                ],
            ],
            'box' => [
                'width' => [
                    'value' => $boxWidth,
                    'formula' => sprintf(
                        'Opening Width - Blum Deduction = %.4f - %.4f = %.4f"',
                        $openingWidth, self::BLUM_SIDE_DEDUCTION, $boxWidth
                    ),
                ],
                'height_exact' => [
                    'value' => $boxHeightExact,
                    'formula' => sprintf(
                        'Face Height - Blum Height Deduction = %.4f - %.4f = %.4f"',
                        $faceHeight, self::BLUM_HEIGHT_DEDUCTION, $boxHeightExact
                    ),
                ],
                'height_shop' => [
                    'value' => $boxHeightShop,
                    'formula' => sprintf(
                        'Round down to 1/2" = %.4f" → %.4f"',
                        $boxHeightExact, $boxHeightShop
                    ),
                ],
                'depth' => [
                    'value' => $boxDepth,
                    'formula' => sprintf('Slide Length = %d"', $slideLength),
                ],
                'depth_shop' => [
                    'value' => $boxDepthShop,
                    'formula' => sprintf(
                        'Slide Length + 1/4" = %d + 0.25 = %.4f"',
                        $slideLength, $boxDepthShop
                    ),
                ],
            ],
            'pieces' => [
                'sides' => [
                    'qty' => 2,
                    'width' => $sideHeight,
                    'length' => $sideLength,
                    'thickness' => self::DRAWER_SIDE_THICKNESS,
                    'material' => '1/2" Plywood',
                ],
                'front_back' => [
                    'qty' => 2,
                    'width' => $sideHeight,
                    'length' => $frontBackLength,
                    'thickness' => self::DRAWER_SIDE_THICKNESS,
                    'material' => '1/2" Plywood',
                ],
                'bottom' => [
                    'qty' => 1,
                    'width' => $bottomWidth,
                    'length' => $bottomLength,
                    'thickness' => self::DRAWER_BOTTOM_THICKNESS,
                    'material' => '1/4" Plywood',
                ],
            ],
            'hardware' => [
                'slides' => [
                    'type' => 'Blum TANDEM 563H',
                    'length' => $slideLength,
                    'qty' => 2,
                ],
            ],
            'note' => $shape === 'u_shaped' ? 'U-Shaped drawer for sink plumbing clearance' : null,
        ];
    }

    /**
     * Calculate door dimensions
     */
    protected function calculateDoor(int $index, float $faceHeight, float $openingWidth, array $faceFrame, array $s): array
    {
        $doorWidth = $openingWidth - (2 * $s['face_frame_door_gap']);
        $doorHeight = $faceHeight - (2 * $s['face_frame_door_gap']);

        return [
            'type' => 'door',
            'index' => $index,
            'face_height' => $faceHeight,
            'width' => [
                'value' => $doorWidth,
                'formula' => sprintf(
                    'Opening Width - (2 × Door Gap) = %.4f - %.4f = %.4f"',
                    $openingWidth, 2 * $s['face_frame_door_gap'], $doorWidth
                ),
            ],
            'height' => [
                'value' => $doorHeight,
                'formula' => sprintf(
                    'Face Height - (2 × Door Gap) = %.4f - %.4f = %.4f"',
                    $faceHeight, 2 * $s['face_frame_door_gap'], $doorHeight
                ),
            ],
        ];
    }

    /**
     * Calculate stretcher count and positions
     */
    protected function calculateStretchers(
        array $components,
        array $box,
        float $cabinetDepth,
        string $cabinetType,
        array $standards
    ): array {
        $s = $standards['values'];
        $isSink = in_array($cabinetType, ['sink_base', 'vanity_sink', 'kitchen_sink']);
        $insideWidth = $box['inside_width']['value'];

        // Count components
        $drawerCount = count(array_filter($components, fn($c) => ($c['type'] ?? '') === 'drawer'));
        $falseFrontCount = count(array_filter($components, fn($c) => ($c['type'] ?? '') === 'false_front'));

        // Sink cabinets: NO stretchers (open top for sink/plumbing)
        if ($isSink) {
            return [
                'count' => [
                    'value' => 0,
                    'formula' => 'Sink cabinet: No stretchers (open top for sink/plumbing access)',
                ],
                'positions' => [],
                'note' => 'TCS Rule: Sink cabinets have no top stretchers, sides extend +0.75" for countertop support',
            ];
        }

        // Standard calculation: 2 (front+back) + drawer_supports - FF_backings
        // FF backings serve as stretchers, so they reduce the count
        // Bottom drawer doesn't need support stretcher (mounts on cabinet bottom)
        $drawerSupports = max(0, $drawerCount - 1);
        $stretcherCount = 2 + $drawerSupports - $falseFrontCount;

        $countFormula = sprintf(
            '2 (front+back) + %d drawer supports - %d FF backings = %d',
            $drawerSupports, $falseFrontCount, $stretcherCount
        );

        // Calculate positions
        $positions = [];
        $stretcherNum = 1;

        // Front stretcher (always)
        $positions[] = [
            'number' => $stretcherNum++,
            'position' => 'front',
            'width' => $insideWidth,
            'depth' => $s['stretcher_depth'],
            'thickness' => $s['stretcher_thickness'],
            'position_from_front' => 0,
            'position_from_top' => 0,
        ];

        // Back stretcher (always)
        $positions[] = [
            'number' => $stretcherNum++,
            'position' => 'back',
            'width' => $insideWidth,
            'depth' => $s['stretcher_depth'],
            'thickness' => $s['stretcher_thickness'],
            'position_from_front' => $cabinetDepth - $s['stretcher_depth'] - $s['back_panel_thickness'],
            'position_from_top' => 0,
        ];

        // Drawer support stretchers (between drawers)
        if ($drawerSupports > 0 && $falseFrontCount === 0) {
            $positionFromTop = 0;
            $componentGap = $s['component_gap'];

            // Calculate positions for drawer support stretchers
            foreach ($components as $index => $component) {
                if (($component['type'] ?? '') === 'drawer') {
                    $positionFromTop += ($component['height'] ?? 0) + $componentGap;

                    // Add stretcher after each drawer except the last
                    if ($index < count($components) - 1) {
                        $positions[] = [
                            'number' => $stretcherNum++,
                            'position' => 'drawer_support',
                            'width' => $insideWidth,
                            'depth' => $s['stretcher_depth'],
                            'thickness' => $s['stretcher_thickness'],
                            'position_from_front' => 0.5, // TCS: set back from front
                            'position_from_top' => $positionFromTop - ($componentGap / 2),
                            'note' => 'Centered on gap between drawer faces',
                        ];
                    }
                }
            }
        }

        return [
            'count' => [
                'value' => max(2, count($positions)),
                'formula' => $countFormula,
            ],
            'positions' => $positions,
        ];
    }

    /**
     * Generate complete cut list organized by component type
     */
    protected function generateCutList(array $box, array $faceFrame, array $components, array $stretchers, array $standards): array
    {
        $s = $standards['values'];

        return [
            'cabinet_box' => [
                'material' => '3/4" Plywood',
                'pieces' => [
                    [
                        'part' => 'Left Side',
                        'qty' => 1,
                        'width' => $box['inside_depth']['value'],
                        'length' => $box['side_height']['value'],
                        'thickness' => $s['side_panel_thickness'],
                    ],
                    [
                        'part' => 'Right Side',
                        'qty' => 1,
                        'width' => $box['inside_depth']['value'],
                        'length' => $box['side_height']['value'],
                        'thickness' => $s['side_panel_thickness'],
                    ],
                    [
                        'part' => 'Bottom',
                        'qty' => 1,
                        'width' => $box['inside_width']['value'],
                        'length' => $box['inside_depth']['value'],
                        'thickness' => $s['box_material_thickness'],
                    ],
                    [
                        'part' => 'Back',
                        'qty' => 1,
                        'width' => $box['inside_width']['value'],
                        'length' => $box['back_height']['value'],
                        'thickness' => $s['back_panel_thickness'],
                        'note' => 'TCS: Full 3/4" back',
                    ],
                ],
            ],
            'face_frame' => [
                'material' => '3/4" Hardwood',
                'pieces' => [
                    [
                        'part' => 'Stiles',
                        'qty' => 2,
                        'width' => $faceFrame['stile_width'],
                        'length' => $faceFrame['stile_length']['value'],
                        'thickness' => $s['box_material_thickness'],
                    ],
                    [
                        'part' => 'Top Rail',
                        'qty' => 1,
                        'width' => $faceFrame['rail_width'],
                        'length' => $faceFrame['rail_length']['value'],
                        'thickness' => $s['box_material_thickness'],
                    ],
                    [
                        'part' => 'Bottom Rail',
                        'qty' => 1,
                        'width' => $faceFrame['rail_width'],
                        'length' => $faceFrame['rail_length']['value'],
                        'thickness' => $s['box_material_thickness'],
                    ],
                ],
            ],
            'stretchers' => [
                'material' => '3/4" Plywood',
                'pieces' => array_map(fn($pos) => [
                    'part' => ucfirst($pos['position']) . ' Stretcher #' . $pos['number'],
                    'qty' => 1,
                    'width' => $pos['depth'],
                    'length' => $pos['width'],
                    'thickness' => $pos['thickness'],
                    'note' => $pos['note'] ?? null,
                ], $stretchers['positions']),
            ],
            'components' => $this->generateComponentCutList($components),
        ];
    }

    /**
     * Generate cut list for false fronts and drawers
     */
    protected function generateComponentCutList(array $components): array
    {
        $pieces = [];

        foreach ($components as $comp) {
            $type = $comp['type'] ?? 'unknown';

            if ($type === 'false_front') {
                $pieces[] = [
                    'part' => "False Front Panel #{$comp['index']}",
                    'qty' => 1,
                    'width' => $comp['panel']['width']['value'],
                    'length' => $comp['panel']['height']['value'],
                    'thickness' => $comp['panel']['thickness'],
                    'material' => '3/4" Panel (Paint Grade)',
                ];
                $pieces[] = [
                    'part' => "False Front Backing #{$comp['index']}",
                    'qty' => 1,
                    'width' => $comp['backing']['width']['value'],
                    'length' => $comp['backing']['height']['value'],
                    'thickness' => $comp['backing']['thickness'],
                    'material' => '3/4" Plywood',
                    'note' => 'TCS: Backing serves as stretcher',
                ];
            } elseif ($type === 'drawer') {
                $pieces[] = [
                    'part' => "Drawer #{$comp['index']} Front",
                    'qty' => 1,
                    'width' => $comp['front']['width']['value'],
                    'length' => $comp['front']['height']['value'],
                    'thickness' => 0.75,
                    'material' => '3/4" Panel (Paint Grade)',
                ];
                $pieces[] = [
                    'part' => "Drawer #{$comp['index']} Sides",
                    'qty' => 2,
                    'width' => $comp['pieces']['sides']['width'],
                    'length' => $comp['pieces']['sides']['length'],
                    'thickness' => $comp['pieces']['sides']['thickness'],
                    'material' => $comp['pieces']['sides']['material'],
                ];
                $pieces[] = [
                    'part' => "Drawer #{$comp['index']} Front/Back",
                    'qty' => 2,
                    'width' => $comp['pieces']['front_back']['width'],
                    'length' => $comp['pieces']['front_back']['length'],
                    'thickness' => $comp['pieces']['front_back']['thickness'],
                    'material' => $comp['pieces']['front_back']['material'],
                ];
                $pieces[] = [
                    'part' => "Drawer #{$comp['index']} Bottom",
                    'qty' => 1,
                    'width' => $comp['pieces']['bottom']['width'],
                    'length' => $comp['pieces']['bottom']['length'],
                    'thickness' => $comp['pieces']['bottom']['thickness'],
                    'material' => $comp['pieces']['bottom']['material'],
                ];
            }
        }

        return $pieces;
    }

    /**
     * Validate calculations
     */
    protected function validate(array $box, array $faceFrame, array $components): array
    {
        $errors = [];
        $warnings = [];

        // Check box dimensions are positive
        if ($box['height']['value'] <= 0) {
            $errors[] = 'Box height must be positive';
        }
        if ($box['inside_width']['value'] <= 0) {
            $errors[] = 'Inside width must be positive';
        }

        // Check opening dimensions
        if ($faceFrame['opening_width']['value'] <= 0) {
            $errors[] = 'Face frame opening width must be positive';
        }

        // Check total component height fits
        $totalComponentHeight = 0;
        $componentCount = 0;
        foreach ($components as $comp) {
            $totalComponentHeight += $comp['face_height'] ?? 0;
            $componentCount++;
        }
        $totalWithGaps = $totalComponentHeight + (max(0, $componentCount - 1) * self::DEFAULT_COMPONENT_GAP);

        if ($totalWithGaps > $box['height']['value'] + 0.5) { // Allow 1/2" for overlay
            $errors[] = sprintf(
                'Components (%.4f") exceed box height (%.4f")',
                $totalWithGaps, $box['height']['value']
            );
        } elseif ($totalWithGaps > $box['height']['value']) {
            $warnings[] = 'Components are at maximum height (full overlay expected)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Format dimension with fraction
     */
    protected function formatDimension(float $inches): string
    {
        $whole = floor($inches);
        $fraction = $inches - $whole;

        if ($fraction < 0.03125) {
            return $whole . '"';
        }

        $sixteenths = round($fraction * 16);
        if ($sixteenths == 16) {
            return ($whole + 1) . '"';
        }

        $numerator = $sixteenths;
        $denominator = 16;
        while ($numerator % 2 == 0 && $denominator > 1) {
            $numerator /= 2;
            $denominator /= 2;
        }

        return $whole > 0 ? "{$whole}-{$numerator}/{$denominator}\"" : "{$numerator}/{$denominator}\"";
    }

    /**
     * Round to nearest 1/16" (shop standard)
     */
    public static function roundToSixteenth(float $inches): float
    {
        return round($inches * 16) / 16;
    }
}
