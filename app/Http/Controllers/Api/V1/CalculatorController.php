<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\CabinetCalculatorService;
use App\Services\DrawerConfiguratorService;
use App\Services\StretcherCalculator;

/**
 * Calculator Controller for V1 API
 *
 * Provides calculation endpoints for cabinet design automation.
 * All calculators accept JSON input and return detailed calculations
 * with formulas, dimensions, and cut lists.
 *
 * Available calculators:
 * - cabinet: Full cabinet calculation from exterior dimensions
 * - drawer: Drawer box calculations from opening dimensions
 * - stretcher: Stretcher count and positioning
 * - depth-validation: Validate cabinet depth for drawer slides
 * - cut-list: Generate cut list from dimensions
 * - nesting: Sheet nesting optimization (if available)
 */
class CalculatorController extends \App\Http\Controllers\Api\BaseApiController
{
    protected CabinetCalculatorService $cabinetCalc;
    protected DrawerConfiguratorService $drawerCalc;
    protected StretcherCalculator $stretcherCalc;

    public function __construct(
        CabinetCalculatorService $cabinetCalc,
        DrawerConfiguratorService $drawerCalc,
        StretcherCalculator $stretcherCalc
    ) {
        $this->cabinetCalc = $cabinetCalc;
        $this->drawerCalc = $drawerCalc;
        $this->stretcherCalc = $stretcherCalc;
    }

    /**
     * Calculate full cabinet from exterior dimensions
     *
     * POST /api/v1/calculators/cabinet
     *
     * Input:
     * {
     *   "exterior": {"width": 41.3125, "height": 32.75, "depth": 21},
     *   "toe_kick_height": 4.0,
     *   "cabinet_type": "sink_base",
     *   "components": [
     *     {"type": "false_front", "height": 6.0},
     *     {"type": "drawer", "height": 10.0}
     *   ],
     *   "construction_template_id": null,
     *   "drawer_slide_length": 18,
     *   "auto_adjust_depth": false
     * }
     *
     * Returns:
     * - Input summary
     * - Construction standards used
     * - Box dimensions with formulas
     * - Face frame dimensions
     * - Component dimensions (drawers, false fronts)
     * - Stretcher positions
     * - Complete cut list
     * - Validation results
     */
    public function cabinet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exterior' => 'required|array',
            'exterior.width' => 'required|numeric|min:1',
            'exterior.height' => 'required|numeric|min:1',
            'exterior.depth' => 'required|numeric|min:1',
            'toe_kick_height' => 'nullable|numeric|min:0',
            'cabinet_type' => 'nullable|string|in:base,sink_base,wall,upper,vanity_sink,kitchen_sink,drawer_base,pantry',
            'components' => 'nullable|array',
            'components.*.type' => 'required_with:components|string|in:drawer,false_front,door,shelf',
            'components.*.height' => 'required_with:components|numeric|min:0',
            'components.*.shape' => 'nullable|string|in:standard,u_shaped',
            'construction_template_id' => 'nullable|integer|exists:projects_construction_templates,id',
            'drawer_slide_length' => 'nullable|integer|in:9,12,15,18,21',
            'auto_adjust_depth' => 'nullable|boolean',
            'face_frame_stile' => 'nullable|numeric|min:0',
            'face_frame_rail' => 'nullable|numeric|min:0',
        ]);

        try {
            $result = $this->cabinetCalc->calculateFromExterior($validated);

            return $this->success($result, 'Cabinet calculations complete');
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate drawer box dimensions from opening
     *
     * POST /api/v1/calculators/drawer
     *
     * Input:
     * {
     *   "opening_width": 18,
     *   "opening_height": 6,
     *   "opening_depth": 21,
     *   "drawer_side_thickness": 0.5,
     *   "face_frame_style": "full_overlay"
     * }
     *
     * Returns:
     * - Opening dimensions
     * - Drawer box dimensions (outside, inside, shop)
     * - Bottom panel dimensions
     * - Clearances and deductions
     * - Hardware specifications
     * - Face frame requirements
     * - Validation results
     */
    public function drawer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_width' => 'required|numeric|min:1',
            'opening_height' => 'required|numeric|min:1',
            'opening_depth' => 'required|numeric|min:1',
            'drawer_side_thickness' => 'nullable|numeric|in:0.5,0.625',
            'face_frame_style' => 'nullable|string|in:full_overlay,inset,partial_overlay,frameless',
        ]);

        try {
            $width = $validated['opening_width'];
            $height = $validated['opening_height'];
            $depth = $validated['opening_depth'];
            $thickness = $validated['drawer_side_thickness'] ?? 0.5;
            $style = $validated['face_frame_style'] ?? 'full_overlay';

            $result = $this->drawerCalc->calculateDrawerDimensionsWithFaceFrame(
                $width,
                $height,
                $depth,
                $thickness,
                $style
            );

            return $this->success($result, 'Drawer calculations complete');
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate drawer stack dimensions
     *
     * POST /api/v1/calculators/drawer-stack
     *
     * Input:
     * {
     *   "opening_width": 18,
     *   "total_height": 24,
     *   "opening_depth": 21,
     *   "drawer_count": 3,
     *   "height_distribution": [0.4, 0.3, 0.3],
     *   "face_frame_style": "full_overlay"
     * }
     */
    public function drawerStack(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_width' => 'required|numeric|min:1',
            'total_height' => 'required|numeric|min:1',
            'opening_depth' => 'required|numeric|min:1',
            'drawer_count' => 'required|integer|min:1|max:10',
            'height_distribution' => 'nullable|array',
            'height_distribution.*' => 'numeric|min:0|max:1',
            'face_frame_style' => 'nullable|string|in:full_overlay,inset,partial_overlay,frameless',
        ]);

        try {
            $distribution = $validated['height_distribution'] ?? null;
            $style = $validated['face_frame_style'] ?? 'full_overlay';

            $result = $this->drawerCalc->calculateDrawerStackWithFaceFrame(
                $validated['opening_width'],
                $validated['total_height'],
                $validated['opening_depth'],
                $validated['drawer_count'],
                $distribution,
                $style
            );

            return $this->success($result, 'Drawer stack calculations complete');
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get drawer cut list with fractions
     *
     * POST /api/v1/calculators/drawer/cut-list
     *
     * Returns a formatted cut list suitable for shop use.
     */
    public function drawerCutList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_width' => 'required|numeric|min:1',
            'opening_height' => 'required|numeric|min:1',
            'opening_depth' => 'required|numeric|min:1',
            'drawer_side_thickness' => 'nullable|numeric|in:0.5,0.625',
        ]);

        try {
            $thickness = $validated['drawer_side_thickness'] ?? 0.5;

            $result = $this->drawerCalc->getFormattedCutList(
                $validated['opening_width'],
                $validated['opening_height'],
                $validated['opening_depth'],
                $thickness
            );

            return $this->success($result, 'Cut list generated');
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate cabinet depth for drawer slides
     *
     * POST /api/v1/calculators/depth-validation
     *
     * Input:
     * {
     *   "cabinet_depth": 21,
     *   "slide_length": 18
     * }
     *
     * Returns whether the depth is sufficient, and recommendations.
     */
    public function depthValidation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cabinet_depth' => 'required|numeric|min:1',
            'slide_length' => 'required|integer|in:9,12,15,18,21',
        ]);

        try {
            // Get standards
            $standards = $this->cabinetCalc->calculateFromExterior([
                'exterior' => ['width' => 18, 'height' => 30, 'depth' => $validated['cabinet_depth']],
            ])['construction_standards'] ?? ['values' => []];

            $result = $this->cabinetCalc->validateCabinetDepthForDrawers(
                $validated['cabinet_depth'],
                $validated['slide_length'],
                $standards
            );

            return $this->success($result, $result['is_sufficient'] ? 'Depth is sufficient' : 'Depth is insufficient');
        } catch (\Exception $e) {
            return $this->error('Validation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get maximum slide length for a cabinet depth
     *
     * POST /api/v1/calculators/max-slide
     *
     * Input:
     * {
     *   "cabinet_depth": 21
     * }
     *
     * Returns the longest drawer slide that will fit.
     */
    public function maxSlide(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cabinet_depth' => 'required|numeric|min:1',
        ]);

        try {
            // Get standards
            $standards = $this->cabinetCalc->calculateFromExterior([
                'exterior' => ['width' => 18, 'height' => 30, 'depth' => $validated['cabinet_depth']],
            ])['construction_standards'] ?? ['values' => []];

            $result = $this->cabinetCalc->getMaxSlideForCabinetDepth(
                $validated['cabinet_depth'],
                $standards
            );

            return $this->success($result, 'Slide recommendation calculated');
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate required cabinet depth for a slide length
     *
     * POST /api/v1/calculators/required-depth
     *
     * Input:
     * {
     *   "slide_length": 18
     * }
     *
     * Returns the minimum cabinet depth required.
     */
    public function requiredDepth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slide_length' => 'required|integer|in:9,12,15,18,21',
        ]);

        try {
            // Get default standards
            $standards = $this->cabinetCalc->calculateFromExterior([
                'exterior' => ['width' => 18, 'height' => 30, 'depth' => 21],
            ])['construction_standards'] ?? ['values' => []];

            $result = $this->cabinetCalc->calculateRequiredCabinetDepth(
                $validated['slide_length'],
                $standards
            );

            return $this->success($result, 'Required depth calculated');
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate stretchers for a cabinet
     *
     * POST /api/v1/calculators/stretcher
     *
     * Input:
     * {
     *   "cabinet_id": 123
     * }
     * OR
     * {
     *   "exterior": {"width": 30, "height": 32, "depth": 21},
     *   "cabinet_type": "base",
     *   "components": [{"type": "drawer", "height": 6}]
     * }
     */
    public function stretcher(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cabinet_id' => 'nullable|integer|exists:projects_cabinets,id',
            'exterior' => 'required_without:cabinet_id|array',
            'exterior.width' => 'required_without:cabinet_id|numeric|min:1',
            'exterior.height' => 'required_without:cabinet_id|numeric|min:1',
            'exterior.depth' => 'required_without:cabinet_id|numeric|min:1',
            'cabinet_type' => 'nullable|string',
            'components' => 'nullable|array',
        ]);

        try {
            if (isset($validated['cabinet_id'])) {
                $cabinet = \Webkul\Project\Models\Cabinet::find($validated['cabinet_id']);
                $result = $this->stretcherCalc->calculateStretchers($cabinet);
            } else {
                // Use cabinet calculator for exterior-based calculation
                $fullResult = $this->cabinetCalc->calculateFromExterior($validated);
                $result = $fullResult['calculations']['stretchers'] ?? [];
            }

            return $this->success($result, 'Stretcher calculations complete');
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get face frame style options and information
     *
     * GET /api/v1/calculators/face-frame-styles
     */
    public function faceFrameStyles(): JsonResponse
    {
        $styles = DrawerConfiguratorService::FACE_FRAME_STYLES;

        $result = [
            'styles' => $styles,
            'options' => DrawerConfiguratorService::getFaceFrameStyleOptions(),
            'default' => DrawerConfiguratorService::FACE_FRAME_STYLE_FULL_OVERLAY,
        ];

        return $this->success($result, 'Face frame styles retrieved');
    }

    /**
     * Get minimum cabinet depths reference table
     *
     * GET /api/v1/calculators/min-cabinet-depths
     *
     * Returns a table of slide lengths with Blum and shop minimums.
     */
    public function minCabinetDepths(): JsonResponse
    {
        $result = DrawerConfiguratorService::getAllMinCabinetDepths();

        return $this->success([
            'depths' => $result,
            'note' => 'Blum values are official spec minimums. Shop values include TCS standard clearances.',
        ], 'Minimum cabinet depths retrieved');
    }

    /**
     * Get Blum TANDEM 563H specifications reference
     *
     * GET /api/v1/calculators/blum-specs
     *
     * Returns the Blum TANDEM 563H hardware specifications.
     */
    public function blumSpecs(): JsonResponse
    {
        $result = [
            'model' => 'Blum TANDEM 563H',
            'drawer_side_options' => [
                '5/8"' => [
                    'side_deduction' => DrawerConfiguratorService::SIDE_DEDUCTION_5_8,
                    'inside_width_deduction' => DrawerConfiguratorService::INSIDE_WIDTH_DEDUCTION_5_8,
                    'bottom_recess' => DrawerConfiguratorService::BOTTOM_RECESS_5_8,
                ],
                '1/2"' => [
                    'side_deduction' => DrawerConfiguratorService::SIDE_DEDUCTION_1_2,
                    'inside_width_deduction' => DrawerConfiguratorService::INSIDE_WIDTH_DEDUCTION_1_2,
                    'bottom_recess' => DrawerConfiguratorService::BOTTOM_RECESS_1_2,
                ],
            ],
            'clearances' => [
                'top' => DrawerConfiguratorService::TOP_CLEARANCE,
                'bottom' => DrawerConfiguratorService::BOTTOM_CLEARANCE,
                'height_deduction' => DrawerConfiguratorService::HEIGHT_DEDUCTION,
            ],
            'mounting' => [
                'runner_height_from_bottom' => DrawerConfiguratorService::RUNNER_MOUNTING_HEIGHT_INCHES,
                'runner_setback' => DrawerConfiguratorService::RUNNER_SETBACK_INCHES,
                'front_holes' => [
                    DrawerConfiguratorService::FRONT_HOLE_1_INCHES,
                    DrawerConfiguratorService::FRONT_HOLE_2_INCHES,
                ],
                'screw_size' => DrawerConfiguratorService::MOUNTING_SCREW_SIZE,
            ],
            'locking_device' => [
                'bore_diameter' => DrawerConfiguratorService::LOCKING_DEVICE_BORE_DIAMETER_INCHES,
                'bore_depth' => DrawerConfiguratorService::LOCKING_DEVICE_BORE_DEPTH_INCHES,
            ],
            'rear_hook' => [
                'bore_diameter' => DrawerConfiguratorService::REAR_HOOK_BORE_DIAMETER_INCHES,
                'position_from_bottom' => DrawerConfiguratorService::REAR_HOOK_POSITION_FROM_BOTTOM_INCHES,
                'position_from_side' => DrawerConfiguratorService::REAR_HOOK_POSITION_FROM_SIDE_INCHES,
            ],
            'construction' => [
                'material_thickness' => DrawerConfiguratorService::MATERIAL_THICKNESS,
                'bottom_thickness' => DrawerConfiguratorService::BOTTOM_THICKNESS,
                'dado_depth' => DrawerConfiguratorService::DADO_DEPTH,
                'bottom_dado_height' => DrawerConfiguratorService::BOTTOM_DADO_HEIGHT,
                'shop_depth_addition' => DrawerConfiguratorService::SHOP_DEPTH_ADDITION,
                'rear_clearance' => DrawerConfiguratorService::DRAWER_REAR_CLEARANCE,
            ],
        ];

        return $this->success($result, 'Blum TANDEM 563H specifications retrieved');
    }

    /**
     * Quick quote for drawer hardware
     *
     * POST /api/v1/calculators/drawer/quick-quote
     *
     * Returns dimensions and hardware requirements in one call.
     */
    public function drawerQuickQuote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_width' => 'required|numeric|min:1',
            'opening_height' => 'required|numeric|min:1',
            'opening_depth' => 'required|numeric|min:1',
            'drawer_count' => 'nullable|integer|min:1|max:10',
            'drawer_side_thickness' => 'nullable|numeric|in:0.5,0.625',
        ]);

        try {
            $count = $validated['drawer_count'] ?? 1;
            $thickness = $validated['drawer_side_thickness'] ?? 0.5;

            $result = $this->drawerCalc->getQuickQuote(
                $validated['opening_width'],
                $validated['opening_height'],
                $validated['opening_depth'],
                $count,
                $thickness
            );

            return $this->success($result, 'Quick quote generated');
        } catch (\Exception $e) {
            return $this->error('Quote failed: ' . $e->getMessage(), 500);
        }
    }
}
