<?php

namespace Webkul\Project\Services\Calculators;

/**
 * Board Foot Calculator Service
 *
 * Provides calculations for dimensional lumber and sheet goods commonly used in cabinet making.
 *
 * Key formulas:
 * - Board Feet (BF) = (T × W × L) / 144, where T, W, L are in inches
 * - Linear Feet to BF: LF × width_inches × thickness_inches / 144
 * - Square Feet to BF: SQFT × thickness_inches / 12
 *
 * Common wood product conversions:
 * - 4/4 lumber = 1" nominal thickness
 * - 5/4 lumber = 1.25" nominal thickness
 * - 6/4 lumber = 1.5" nominal thickness
 * - 8/4 lumber = 2" nominal thickness
 * - Sheet goods (plywood, MDF) typically in SQFT at various thicknesses
 */
class BoardFootCalculator
{
    /**
     * Standard lumber thicknesses in inches (quarters notation to actual).
     */
    protected array $quartersToActual = [
        '4/4' => 0.8125,  // Actually ~13/16" after milling
        '5/4' => 1.0625,  // Actually ~1-1/16" after milling
        '6/4' => 1.3125,  // Actually ~1-5/16" after milling
        '8/4' => 1.75,    // Actually ~1-3/4" after milling
    ];

    /**
     * Common face frame profile widths in inches.
     */
    protected array $faceFrameWidths = [
        'stile' => 1.5,
        'rail' => 1.5,
        'mullion' => 1.5,
    ];

    /**
     * Calculate board feet from dimensional inches.
     *
     * @param float $thickness Thickness in inches
     * @param float $width Width in inches
     * @param float $length Length in inches
     * @return float Board feet
     */
    public function calculateBoardFeet(float $thickness, float $width, float $length): float
    {
        return ($thickness * $width * $length) / 144;
    }

    /**
     * Calculate board feet from linear feet and profile dimensions.
     *
     * @param float $linearFeet Length in linear feet
     * @param float $widthInches Profile width in inches
     * @param float $thicknessInches Profile thickness in inches
     * @return float Board feet
     */
    public function linearFeetToBoardFeet(float $linearFeet, float $widthInches, float $thicknessInches): float
    {
        // LF × 12 (to inches) × width × thickness / 144
        return ($linearFeet * 12 * $widthInches * $thicknessInches) / 144;
    }

    /**
     * Calculate board feet from square feet and thickness.
     *
     * @param float $squareFeet Area in square feet
     * @param float $thicknessInches Thickness in inches
     * @return float Board feet
     */
    public function squareFeetToBoardFeet(float $squareFeet, float $thicknessInches): float
    {
        // SQFT × thickness / 12
        return $squareFeet * $thicknessInches / 12;
    }

    /**
     * Calculate face frame linear feet requirements.
     *
     * @param float $openingWidthInches Cabinet opening width
     * @param float $openingHeightInches Cabinet opening height
     * @param int $divisions Number of vertical divisions (mullions)
     * @param float $stileWidth Stile width in inches (default 1.5")
     * @param float $railWidth Rail width in inches (default 1.5")
     * @return array Breakdown of linear feet needed
     */
    public function calculateFaceFrameLinearFeet(
        float $openingWidthInches,
        float $openingHeightInches,
        int $divisions = 0,
        float $stileWidth = 1.5,
        float $railWidth = 1.5
    ): array {
        // Stiles (2 for outer edges)
        $stileCount = 2 + $divisions;
        $stileLinearFeet = ($stileCount * $openingHeightInches) / 12;

        // Rails (top and bottom)
        $railCount = 2;
        $railWidth = $openingWidthInches - (2 * $stileWidth);
        $railLinearFeet = ($railCount * $railWidth) / 12;

        // Mullions
        $mullionLinearFeet = $divisions > 0 
            ? ($divisions * ($openingHeightInches - (2 * $railWidth))) / 12 
            : 0;

        return [
            'stiles' => round($stileLinearFeet, 2),
            'rails' => round($railLinearFeet, 2),
            'mullions' => round($mullionLinearFeet, 2),
            'total_linear_feet' => round($stileLinearFeet + $railLinearFeet + $mullionLinearFeet, 2),
            'breakdown' => [
                'stile_count' => $stileCount,
                'rail_count' => $railCount,
                'mullion_count' => $divisions,
            ],
        ];
    }

    /**
     * Calculate face frame board feet from linear feet.
     *
     * @param float $linearFeet Total linear feet of face frame
     * @param float $profileWidth Profile width in inches (typically 1.5")
     * @param string $stockThickness Stock thickness in quarters notation (e.g., "4/4")
     * @return float Board feet needed
     */
    public function calculateFaceFrameBoardFeet(
        float $linearFeet,
        float $profileWidth = 1.5,
        string $stockThickness = '4/4'
    ): float {
        $thickness = $this->getActualThickness($stockThickness);
        return $this->linearFeetToBoardFeet($linearFeet, $profileWidth, $thickness);
    }

    /**
     * Calculate sheet goods requirements in square feet.
     *
     * @param array $panels Array of panels with width and height in inches
     * @param float $wasteFactor Waste/kerf allowance multiplier (default 1.15 = 15% waste)
     * @return array Square feet needed and panel count estimate
     */
    public function calculateSheetGoodsSquareFeet(array $panels, float $wasteFactor = 1.15): array
    {
        $totalSqft = 0;
        $panelDetails = [];

        foreach ($panels as $panel) {
            $sqft = ($panel['width_inches'] * $panel['height_inches']) / 144;
            $qty = $panel['quantity'] ?? 1;
            $subtotal = $sqft * $qty;
            
            $panelDetails[] = [
                'width' => $panel['width_inches'],
                'height' => $panel['height_inches'],
                'quantity' => $qty,
                'sqft_each' => round($sqft, 3),
                'sqft_total' => round($subtotal, 3),
            ];

            $totalSqft += $subtotal;
        }

        $withWaste = $totalSqft * $wasteFactor;
        
        // Standard sheet sizes: 4x8 (32 sqft), 4x4 (16 sqft), 5x5 (25 sqft)
        $fullSheets = ceil($withWaste / 32);

        return [
            'net_sqft' => round($totalSqft, 2),
            'with_waste_sqft' => round($withWaste, 2),
            'waste_factor' => $wasteFactor,
            'estimated_4x8_sheets' => $fullSheets,
            'panels' => $panelDetails,
        ];
    }

    /**
     * Get actual thickness from quarters notation.
     *
     * @param string $quarters Quarters notation (e.g., "4/4", "6/4")
     * @return float Actual thickness in inches
     */
    public function getActualThickness(string $quarters): float
    {
        // If already a number, return it
        if (is_numeric($quarters)) {
            return (float) $quarters;
        }

        // Check predefined quarters
        if (isset($this->quartersToActual[$quarters])) {
            return $this->quartersToActual[$quarters];
        }

        // Parse X/4 format
        if (preg_match('/(\d+)\/4/', $quarters, $matches)) {
            $numerator = (int) $matches[1];
            // Nominal thickness minus typical milling loss
            return ($numerator / 4) - 0.1875;
        }

        // Default to 3/4"
        return 0.75;
    }

    /**
     * Get nominal thickness from quarters notation.
     *
     * @param string $quarters Quarters notation
     * @return float Nominal thickness in inches
     */
    public function getNominalThickness(string $quarters): float
    {
        if (is_numeric($quarters)) {
            return (float) $quarters;
        }

        if (preg_match('/(\d+)\/4/', $quarters, $matches)) {
            return (int) $matches[1] / 4;
        }

        return 0.75;
    }

    /**
     * Calculate lumber cost from board feet and price per BF.
     *
     * @param float $boardFeet Board feet
     * @param float $pricePerBf Price per board foot
     * @return float Total cost
     */
    public function calculateLumberCost(float $boardFeet, float $pricePerBf): float
    {
        return round($boardFeet * $pricePerBf, 2);
    }

    /**
     * Calculate total material requirements for a cabinet.
     *
     * @param array $dimensions Cabinet dimensions (width, height, depth in inches)
     * @param array $options Material options
     * @return array Complete material breakdown
     */
    public function calculateCabinetMaterialRequirements(array $dimensions, array $options = []): array
    {
        $width = $dimensions['width_inches'] ?? $dimensions['length_inches'] ?? 36;
        $height = $dimensions['height_inches'] ?? 30;
        $depth = $dimensions['depth_inches'] ?? $dimensions['width_inches'] ?? 24;

        $caseMaterial = $options['case_material'] ?? 'plywood';
        $caseThickness = $options['case_thickness'] ?? 0.75;

        // Calculate case panels
        $casePanels = [
            ['width_inches' => $height, 'height_inches' => $depth, 'quantity' => 2], // Sides
            ['width_inches' => $width - ($caseThickness * 2), 'height_inches' => $depth, 'quantity' => 2], // Top/Bottom
            ['width_inches' => $width - ($caseThickness * 2), 'height_inches' => $height - ($caseThickness * 2), 'quantity' => 1], // Back (if full)
        ];

        $caseSheetGoods = $this->calculateSheetGoodsSquareFeet($casePanels);

        // Face frame calculations
        $faceFrame = $this->calculateFaceFrameLinearFeet($width, $height);
        $faceFrameBf = $this->calculateFaceFrameBoardFeet(
            $faceFrame['total_linear_feet'],
            $options['face_frame_width'] ?? 1.5,
            $options['face_frame_stock'] ?? '4/4'
        );

        return [
            'case' => [
                'material' => $caseMaterial,
                'thickness' => $caseThickness,
                'sqft' => $caseSheetGoods['with_waste_sqft'],
                'sheets_4x8' => $caseSheetGoods['estimated_4x8_sheets'],
                'details' => $caseSheetGoods,
            ],
            'face_frame' => [
                'linear_feet' => $faceFrame['total_linear_feet'],
                'board_feet' => round($faceFrameBf, 2),
                'breakdown' => $faceFrame,
            ],
            'summary' => [
                'total_sheet_goods_sqft' => $caseSheetGoods['with_waste_sqft'],
                'total_hardwood_bf' => round($faceFrameBf, 2),
            ],
        ];
    }
}
