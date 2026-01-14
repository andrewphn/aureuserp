<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

/**
 * Calculates adjustable shelf dimensions based on cabinet opening dimensions
 * and shop practices.
 * 
 * Shop Practice (from carpenter interview):
 * - Pin holes: 5mm diameter, 2" from front edge, 2" from back edge
 * - Vertical spacing: 2" between holes (4" total adjustment range)
 * - Center support: Add 3rd column of pins at 28"+ width
 * - Notch depth: 3/8" (standard) or 5/8" (deep) depending on hardware
 * - Edge banding: Front edge ONLY
 * - Material: Matches cabinet box material (pre-finished maple typical)
 */
class ShelfConfiguratorService
{
    protected ShelfHardwareService $hardwareService;

    public function __construct(ShelfHardwareService $hardwareService)
    {
        $this->hardwareService = $hardwareService;
    }

    // ========================================
    // PIN HOLE LAYOUT CONSTANTS
    // ========================================
    
    /**
     * Distance from front edge to front pin hole column.
     * Shop standard: 2" from front edge.
     */
    public const PIN_SETBACK_FRONT_INCHES = 2.0;
    
    /**
     * Distance from back edge to back pin hole column.
     * Shop standard: 2" from back edge.
     */
    public const PIN_SETBACK_BACK_INCHES = 2.0;
    
    /**
     * Vertical spacing between pin holes.
     * Shop standard: 2" apart for adjustment range.
     * Total range: 4" (2" up, 2" down from nominal position).
     */
    public const PIN_VERTICAL_SPACING_INCHES = 2.0;
    
    /**
     * Pin hole diameter.
     * Industry standard: 5mm
     */
    public const PIN_HOLE_DIAMETER_MM = 5.0;
    public const PIN_HOLE_DIAMETER_INCHES = 0.1969;  // 5mm ≈ 0.1969"

    // ========================================
    // CENTER SUPPORT THRESHOLD
    // ========================================
    
    /**
     * DEPTH threshold for adding center support column.
     * At 28" or greater DEPTH, add a 3rd column of pin holes
     * in the center (depth ÷ 2) to prevent shelf sag.
     * 
     * Pin holes are on cabinet SIDES, so center support
     * is about front-to-back span (depth), not left-to-right (width).
     */
    public const CENTER_SUPPORT_THRESHOLD_INCHES = 28.0;

    // ========================================
    // NOTCH SPECIFICATIONS
    // ========================================
    
    /**
     * Standard notch depth for shelf pins.
     * Most common for standard 5mm pins.
     */
    public const NOTCH_DEPTH_STANDARD_INCHES = 0.375;  // 3/8"
    
    /**
     * Deep notch depth for larger/deeper pins.
     */
    public const NOTCH_DEPTH_DEEP_INCHES = 0.625;  // 5/8"
    
    /**
     * Default notch depth.
     */
    public const NOTCH_DEPTH_DEFAULT_INCHES = 0.375;  // 3/8"

    // ========================================
    // CLEARANCE CONSTANTS
    // ========================================
    
    /**
     * Side clearance per side.
     * Allows shelf to slide in/out of cabinet easily.
     */
    public const SIDE_CLEARANCE_INCHES = 0.0625;  // 1/16" per side
    
    /**
     * Total side clearance (both sides).
     */
    public const TOTAL_SIDE_CLEARANCE_INCHES = 0.125;  // 1/8" total
    
    /**
     * Back clearance.
     * Accounts for back panel and pin protrusion.
     */
    public const BACK_CLEARANCE_INCHES = 0.25;  // 1/4"

    // ========================================
    // MINIMUM OPENING REQUIREMENTS
    // ========================================
    
    /**
     * Absolute minimum opening height (technically possible but not recommended).
     */
    public const MIN_OPENING_HEIGHT_ABSOLUTE_INCHES = 0.75;  // 3/4"
    
    /**
     * Recommended minimum opening height for practical use.
     */
    public const MIN_OPENING_HEIGHT_RECOMMENDED_INCHES = 5.5;  // 5-1/2"

    // ========================================
    // MATERIAL CONSTANTS
    // ========================================
    
    /**
     * Standard shelf thickness.
     */
    public const THICKNESS_STANDARD_INCHES = 0.75;  // 3/4"
    
    /**
     * Thin shelf thickness.
     */
    public const THICKNESS_THIN_INCHES = 0.5;  // 1/2"

    // ========================================
    // EDGE BANDING
    // ========================================
    
    /**
     * Edge banding thickness (typical PVC/veneer).
     */
    public const EDGE_BAND_THICKNESS_INCHES = 0.02;

    // ========================================
    // MAIN CALCULATION METHOD
    // ========================================

    /**
     * Calculate shelf dimensions from cabinet opening dimensions.
     * 
     * @param float $openingWidth Cabinet opening width in inches
     * @param float $openingHeight Cabinet opening height in inches
     * @param float $openingDepth Cabinet opening depth in inches
     * @param float $thickness Shelf material thickness (default 3/4")
     * @param string $notchType Notch depth type: 'standard' or 'deep'
     * @return array Calculated shelf dimensions and specifications
     */
    public function calculateShelfDimensions(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        float $thickness = 0.75,
        string $notchType = 'standard'
    ): array {
        // Calculate cut dimensions
        $cutWidth = $openingWidth - self::TOTAL_SIDE_CLEARANCE_INCHES;
        $cutDepth = $openingDepth - self::BACK_CLEARANCE_INCHES;
        
        // Determine if center support is needed (based on DEPTH, not width)
        // Pin holes are on cabinet sides, spanning front-to-back (depth)
        $needsCenterSupport = $this->needsCenterSupport($openingDepth);
        
        // Get pin hole layout (based on depth)
        $pinHoleLayout = $this->getPinHoleLayout($openingDepth);
        
        // Get notch specifications
        $notchDepth = $notchType === 'deep' 
            ? self::NOTCH_DEPTH_DEEP_INCHES 
            : self::NOTCH_DEPTH_STANDARD_INCHES;
        $notchCount = $needsCenterSupport ? 6 : 4;  // 2 extra for center support
        
        // Calculate edge banding (front edge only)
        $edgeBandLength = $cutWidth;
        
        // Get hardware
        $pinQuantity = $notchCount;  // One pin per notch
        
        // Validation
        $validation = $this->validateDimensions($openingWidth, $openingHeight, $openingDepth);

        return [
            'opening' => [
                'width' => $openingWidth,
                'height' => $openingHeight,
                'depth' => $openingDepth,
            ],
            'shelf' => [
                'cut_width' => round($cutWidth, 4),
                'cut_depth' => round($cutDepth, 4),
                'thickness' => $thickness,
            ],
            'clearances' => [
                'side_per_side' => self::SIDE_CLEARANCE_INCHES,
                'side_total' => self::TOTAL_SIDE_CLEARANCE_INCHES,
                'back' => self::BACK_CLEARANCE_INCHES,
            ],
            'pin_holes' => $pinHoleLayout,
            'notches' => [
                'depth' => $notchDepth,
                'depth_fraction' => self::toFraction($notchDepth),
                'count' => $notchCount,
                'type' => $notchType,
            ],
            'center_support' => [
                'required' => $needsCenterSupport,
                'threshold' => self::CENTER_SUPPORT_THRESHOLD_INCHES,
            ],
            'edge_banding' => [
                'front' => true,
                'back' => false,
                'sides' => false,
                'length_inches' => round($edgeBandLength, 4),
                'length_fraction' => self::toFraction($edgeBandLength),
            ],
            'hardware' => [
                'shelf_pins' => [
                    'quantity' => $pinQuantity,
                    'diameter_mm' => self::PIN_HOLE_DIAMETER_MM,
                ],
            ],
            'validation' => $validation,
        ];
    }

    // ========================================
    // PIN HOLE LAYOUT
    // ========================================

    /**
     * Determine if shelf DEPTH requires center support.
     * 
     * Pin holes are on cabinet sides, spanning front-to-back (depth).
     * Center support prevents sag across the depth dimension.
     * 
     * @param float $depth Opening depth in inches
     * @return bool True if depth >= 28"
     */
    public function needsCenterSupport(float $depth): bool
    {
        return $depth >= self::CENTER_SUPPORT_THRESHOLD_INCHES;
    }

    /**
     * Get pin hole column positions for cabinet sides.
     * 
     * Columns span the DEPTH dimension (front-to-back).
     * 
     * @param float $depth Opening depth in inches
     * @return array Array of column definitions
     */
    public function getPinHoleColumns(float $depth): array
    {
        $columns = [
            [
                'position' => 'front',
                'setback_from_edge' => self::PIN_SETBACK_FRONT_INCHES,
                'edge' => 'front',
            ],
            [
                'position' => 'back',
                'setback_from_edge' => self::PIN_SETBACK_BACK_INCHES,
                'edge' => 'back',
            ],
        ];
        
        if ($this->needsCenterSupport($depth)) {
            $columns[] = [
                'position' => 'center',
                'setback_from_edge' => $depth / 2,  // Center of depth
                'edge' => 'front',  // Measured from front
            ];
        }
        
        return $columns;
    }

    /**
     * Get complete pin hole layout for cabinet sides.
     * 
     * Pin holes span the DEPTH dimension (front-to-back on cabinet sides).
     * 
     * @param float $depth Opening depth in inches
     * @return array Complete pin hole layout specifications
     */
    public function getPinHoleLayout(float $depth): array
    {
        $columns = $this->getPinHoleColumns($depth);
        
        return [
            'columns' => $columns,
            'column_count' => count($columns),
            'hole_diameter_mm' => self::PIN_HOLE_DIAMETER_MM,
            'hole_diameter_inches' => self::PIN_HOLE_DIAMETER_INCHES,
            'vertical_spacing' => self::PIN_VERTICAL_SPACING_INCHES,
            'adjustment_range' => self::PIN_VERTICAL_SPACING_INCHES * 2,  // 4" total (2 up, 2 down)
            'drill_bit' => '5mm brad point',
            'setbacks' => [
                'front' => self::PIN_SETBACK_FRONT_INCHES,
                'back' => self::PIN_SETBACK_BACK_INCHES,
                'center' => $this->needsCenterSupport($depth) ? $depth / 2 : null,
            ],
        ];
    }

    // ========================================
    // CUT LIST
    // ========================================

    /**
     * Generate cut list for shelf.
     * 
     * @param float $openingWidth Cabinet opening width
     * @param float $openingHeight Cabinet opening height
     * @param float $openingDepth Cabinet opening depth
     * @param float $thickness Material thickness
     * @return array Cut list with specifications
     */
    public function getCutList(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        float $thickness = 0.75
    ): array {
        $dims = $this->calculateShelfDimensions($openingWidth, $openingHeight, $openingDepth, $thickness);
        
        $needsCenter = $dims['center_support']['required'];
        
        return [
            'opening' => $dims['opening'],
            'shelf' => $dims['shelf'],
            'cut_list' => [
                'shelf_panel' => [
                    'quantity' => 1,
                    'material' => '3/4" plywood (match cabinet box)',
                    'width' => round($dims['shelf']['cut_width'], 4),
                    'width_fraction' => self::toFraction($dims['shelf']['cut_width']),
                    'depth' => round($dims['shelf']['cut_depth'], 4),
                    'depth_fraction' => self::toFraction($dims['shelf']['cut_depth']),
                    'thickness' => $thickness,
                    'notes' => 'Cut notches at corners for shelf pins',
                ],
            ],
            'notch_specs' => [
                'depth' => $dims['notches']['depth'],
                'depth_fraction' => $dims['notches']['depth_fraction'],
                'count' => $dims['notches']['count'],
                'locations' => $needsCenter 
                    ? 'All 4 corners + 2 center notches (for center support)'
                    : 'All 4 corners',
            ],
            'edge_banding' => [
                'edge' => 'Front only',
                'length' => $dims['edge_banding']['length_inches'],
                'length_fraction' => $dims['edge_banding']['length_fraction'],
                'material' => 'Match cabinet interior',
            ],
            'hardware' => [
                'shelf_pins' => [
                    'quantity' => $dims['hardware']['shelf_pins']['quantity'],
                    'size' => '5mm spoon-style',
                    'notes' => 'Generic - interchangeable',
                ],
            ],
            'cnc_operations' => [
                'cabinet_sides' => [
                    'operation' => 'Drill shelf pin holes',
                    'tool' => '5mm brad point drill bit',
                    'hole_depth' => $dims['notches']['depth_fraction'],
                    'columns' => $dims['pin_holes']['column_count'],
                    'vertical_spacing' => self::toFraction(self::PIN_VERTICAL_SPACING_INCHES),
                ],
            ],
            'validation' => $dims['validation'],
        ];
    }

    /**
     * Get formatted cut list with fractional dimensions.
     */
    public function getFormattedCutList(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth,
        float $thickness = 0.75
    ): array {
        $cutList = $this->getCutList($openingWidth, $openingHeight, $openingDepth, $thickness);
        
        $formatted = [
            'opening' => sprintf('%s W × %s H × %s D',
                self::toFraction($openingWidth),
                self::toFraction($openingHeight),
                self::toFraction($openingDepth)
            ),
            'shelf' => sprintf('%s W × %s D × %s thick',
                self::toFraction($cutList['shelf']['cut_width']),
                self::toFraction($cutList['shelf']['cut_depth']),
                self::toFraction($thickness)
            ),
            'notches' => sprintf('%d notches at %s deep',
                $cutList['notch_specs']['count'],
                $cutList['notch_specs']['depth_fraction']
            ),
            'edge_banding' => sprintf('%s (front edge only)',
                $cutList['edge_banding']['length_fraction']
            ),
            'pins' => sprintf('%d × 5mm shelf pins',
                $cutList['hardware']['shelf_pins']['quantity']
            ),
            'center_support' => $cutList['notch_specs']['count'] > 4 
                ? 'Yes (width ≥ 28")' 
                : 'No',
        ];

        return $formatted;
    }

    // ========================================
    // VALIDATION
    // ========================================

    /**
     * Validate opening dimensions.
     */
    protected function validateDimensions(
        float $openingWidth,
        float $openingHeight,
        float $openingDepth
    ): array {
        $issues = [];
        $warnings = [];
        $valid = true;

        // Check minimum opening height
        if ($openingHeight < self::MIN_OPENING_HEIGHT_ABSOLUTE_INCHES) {
            $issues[] = sprintf(
                'Opening height (%.2f") is below absolute minimum (%.2f")',
                $openingHeight,
                self::MIN_OPENING_HEIGHT_ABSOLUTE_INCHES
            );
            $valid = false;
        } elseif ($openingHeight < self::MIN_OPENING_HEIGHT_RECOMMENDED_INCHES) {
            $warnings[] = sprintf(
                'Opening height (%.2f") is below recommended minimum (%.2f")',
                $openingHeight,
                self::MIN_OPENING_HEIGHT_RECOMMENDED_INCHES
            );
        }

        // Check minimum width (must fit pin setbacks)
        $minWidth = self::PIN_SETBACK_FRONT_INCHES + self::PIN_SETBACK_BACK_INCHES + 2;
        if ($openingWidth < $minWidth) {
            $issues[] = sprintf(
                'Opening width (%.2f") is too narrow for pin layout (min %.2f")',
                $openingWidth,
                $minWidth
            );
            $valid = false;
        }

        // Check for center support threshold (based on DEPTH)
        if ($openingDepth >= self::CENTER_SUPPORT_THRESHOLD_INCHES) {
            $warnings[] = sprintf(
                'Depth (%.2f") requires center support column (threshold: %.2f")',
                $openingDepth,
                self::CENTER_SUPPORT_THRESHOLD_INCHES
            );
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    /**
     * Convert decimal inches to fractional string.
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
}
